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
     * Return all project rows ordered by sync_timestamp DESC (most recently
     * synced first).  Used by ProjectController::listProjects().
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `id`, `github_project_id`, `github_owner`, `project_number`,
                    `name`, `current_iteration`, `sync_timestamp`, `created_at`
               FROM `projects`
              ORDER BY `sync_timestamp` DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Return a single project row by its local auto-increment ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `github_project_id`, `github_owner`, `project_number`,
                    `name`, `current_iteration`, `sync_timestamp`, `created_at`
               FROM `projects`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Replace the stored iteration (sprint) configuration of a project.
     *
     * @param array<int,array{title?:string,startDate?:string|null,duration?:int|null,completed?:bool}> $iterations
     */
    public function syncIterations(int $projectId, array $iterations): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `project_iterations` (`project_id`, `title`, `start_date`, `duration_days`, `is_completed`)
             VALUES (:project_id, :title, :start_date, :duration_days, :is_completed)
             ON DUPLICATE KEY UPDATE
                 `start_date`    = VALUES(`start_date`),
                 `duration_days` = VALUES(`duration_days`),
                 `is_completed`  = VALUES(`is_completed`)'
        );

        foreach ($iterations as $it) {
            $title = trim((string) ($it['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $stmt->execute([
                'project_id'    => $projectId,
                'title'         => $title,
                'start_date'    => $it['startDate'] ?? null,
                'duration_days' => isset($it['duration']) ? (int) $it['duration'] : null,
                'is_completed'  => !empty($it['completed']) ? 1 : 0,
            ]);
        }
    }

    /**
     * Record which iteration is running now (null clears it).
     */
    public function updateCurrentIteration(int $projectId, ?string $title): void
    {
        $stmt = $this->pdo->prepare('UPDATE `projects` SET `current_iteration` = ? WHERE `id` = ?');
        $stmt->execute([$title, $projectId]);
    }

    /**
     * List every iteration known for a project, newest first.
     *
     * Union of the GitHub iteration configuration (project_iterations), iteration
     * names found on issues, and names present in burndown_daily — so sprints
     * appear even if GitHub no longer lists them or they have no issues yet.
     *
     * @return array<int,array{
     *   name: string, start_date: ?string, duration_days: ?int,
     *   is_completed: bool, has_data: bool
     * }>
     */
    public function findIterations(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.`name`,
                    pi.`start_date`,
                    pi.`duration_days`,
                    COALESCE(pi.`is_completed`, 0) AS is_completed,
                    EXISTS (SELECT 1 FROM `burndown_daily` b
                             WHERE b.`project_id` = :p4 AND b.`iteration` = t.`name`) AS has_data
               FROM (
                    SELECT `title` AS `name` FROM `project_iterations` WHERE `project_id` = :p1
                    UNION
                    SELECT `iteration` FROM `issues` WHERE `project_id` = :p2 AND `iteration` IS NOT NULL
                    UNION
                    SELECT `iteration` FROM `burndown_daily` WHERE `project_id` = :p3
               ) t
               LEFT JOIN `project_iterations` pi
                      ON pi.`project_id` = :p5 AND pi.`title` = t.`name`
              ORDER BY pi.`start_date` IS NULL, pi.`start_date` DESC, t.`name` DESC'
        );
        $stmt->execute(['p1' => $projectId, 'p2' => $projectId, 'p3' => $projectId, 'p4' => $projectId, 'p5' => $projectId]);

        return array_map(static fn(array $r): array => [
            'name'          => (string) $r['name'],
            'start_date'    => $r['start_date'] !== null ? (string) $r['start_date'] : null,
            'duration_days' => $r['duration_days'] !== null ? (int) $r['duration_days'] : null,
            'is_completed'  => (bool) $r['is_completed'],
            'has_data'      => (bool) $r['has_data'],
        ], $stmt->fetchAll());
    }
}
