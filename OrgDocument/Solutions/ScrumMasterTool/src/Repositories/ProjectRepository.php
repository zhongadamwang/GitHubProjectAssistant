<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use PDO;

/**
 * ProjectRepository — all database access for the `projects` table.
 *
 * Columns mapped from the GitHub Projects v2 API response:
 *   github_project_id  ← Project::$githubId
 *   github_owner       ← Project::$owner
 *   project_number     ← Project::$number
 *   name               ← Project::$title
 *   sync_timestamp     ← set to NOW() on every upsert
 *
 * `github_repo` is not available from Projects v2 (owner-level projects are
 * not scoped to a single repo); stored as empty string.
 */
final class ProjectRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insert or update the project row from GitHub data.
     * Sets `sync_timestamp` to the current UTC datetime on every call.
     */
    public function upsertFromGitHub(Project $project): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `projects`
                 (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`, `sync_timestamp`)
             VALUES
                 (:github_project_id, :github_owner, :github_repo, :project_number, :name, :sync_timestamp)
             ON DUPLICATE KEY UPDATE
                 `github_owner`    = VALUES(`github_owner`),
                 `project_number`  = VALUES(`project_number`),
                 `name`            = VALUES(`name`),
                 `sync_timestamp`  = VALUES(`sync_timestamp`)'
        );

        $stmt->execute([
            'github_project_id' => $project->githubId,
            'github_owner'      => $project->owner,
            'github_repo'       => '',          // Projects v2 are owner-scoped, not repo-scoped
            'project_number'    => $project->number,
            'name'              => $project->title,
            'sync_timestamp'    => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Return raw DB row for a project by its GitHub node ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findByGitHubId(string $githubId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `projects` WHERE `github_project_id` = ? LIMIT 1'
        );
        $stmt->execute([$githubId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Return the local auto-increment `id` for a GitHub project node ID,
     * or null when not yet persisted.
     */
    public function getLocalId(string $githubId): ?int
    {
        $row = $this->findByGitHubId($githubId);
        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * Return all project rows ordered by sync_timestamp DESC.
     *
     * @return array<int, array<string,mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM `projects` ORDER BY `sync_timestamp` DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Return a single project row by local id, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `projects` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}

