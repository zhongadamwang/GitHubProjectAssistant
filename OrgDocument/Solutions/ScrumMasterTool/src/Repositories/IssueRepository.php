<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Issue;
use PDO;

/**
 * IssueRepository — all database access for the `issues` table.
 *
 * CRITICAL: `estimated_time`, `remaining_time`, and `actual_time` are LOCAL
 * fields owned by the team. They are NEVER written by `upsertFromGitHub()`.
 * They may only be updated through the dedicated time-tracking endpoints (T016).
 *
 * Column mapping from Issue model:
 *   github_issue_id   ← Issue::$contentId  (underlying Issue node ID)
 *   title             ← Issue::$title
 *   status            ← Issue::$state  (lowercased)
 *   assignee          ← first assignee login, or null
 *   labels            ← JSON-encoded labels array
 *   iteration         ← Issue::$customFields['iteration'] or null
 *   github_updated_at ← Issue::$updatedAt
 */
final class IssueRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insert or update an issue row from GitHub data.
     *
     * Uses INSERT … ON DUPLICATE KEY UPDATE. The UNIQUE KEY on
     * `(project_id, github_issue_id)` determines duplicates.
     *
     * Local time fields are excluded from the UPDATE clause — they are
     * never overwritten by a sync operation.
     *
     * @param Issue $issue      Parsed from GitHub
     * @param int   $projectId  Local `projects.id` FK
     */
    public function upsertFromGitHub(Issue $issue, int $projectId): void
    {
        $assignee  = $issue->assignees[0]['login'] ?? null;
        $labels    = !empty($issue->labels)
            ? json_encode($issue->labels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            : null;
        $iteration = $issue->customFields['iteration'] ?? null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`,
                  `assignee`, `labels`, `iteration`, `github_updated_at`)
             VALUES
                 (:project_id, :github_issue_id, :title, :status,
                  :assignee, :labels, :iteration, :github_updated_at)
             ON DUPLICATE KEY UPDATE
                 `title`             = VALUES(`title`),
                 `status`            = VALUES(`status`),
                 `assignee`          = VALUES(`assignee`),
                 `labels`            = VALUES(`labels`),
                 `iteration`         = VALUES(`iteration`),
                 `github_updated_at` = VALUES(`github_updated_at`)'
            // estimated_time, remaining_time, actual_time are intentionally absent
        );

        $stmt->execute([
            'project_id'        => $projectId,
            'github_issue_id'   => $issue->contentId,
            'title'             => $issue->title,
            'status'            => strtolower($issue->state),
            'assignee'          => $assignee,
            'labels'            => $labels,
            'iteration'         => is_string($iteration) ? $iteration : null,
            // Normalize ISO 8601 ('2026-04-01T10:00:00Z') to MySQL DATETIME format
            'github_updated_at' => $issue->updatedAt !== null
                ? rtrim(str_replace('T', ' ', $issue->updatedAt), 'Z')
                : null,
        ]);
    }

    /**
     * Return raw DB row for an issue by its GitHub content node ID and
     * local project ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findByGitHubId(string $contentId, int $projectId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `issues`
              WHERE `github_issue_id` = ? AND `project_id` = ?
              LIMIT 1'
        );
        $stmt->execute([$contentId, $projectId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Return the `github_updated_at` stored for the given issue, or null
     * when the issue does not yet exist locally.
     */
    public function getStoredUpdatedAt(string $contentId, int $projectId): ?string
    {
        $row = $this->findByGitHubId($contentId, $projectId);
        return $row !== null ? ($row['github_updated_at'] ?? null) : null;
    }
}
