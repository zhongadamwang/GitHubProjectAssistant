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

    /**
     * Aggregate time fields grouped by iteration for burndown snapshot capture.
     *
     * Returns one row per distinct non-null iteration value. Null-iteration
     * issues are grouped under an empty string key.
     *
     * @return array<int, array{iteration: string, total_estimated: float, total_remaining: float, open_count: int, closed_count: int}>
     */
    public function aggregateTimeByIteration(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 COALESCE(`iteration`, '') AS `iteration`,
                 SUM(`estimated_time`)     AS `total_estimated`,
                 SUM(`remaining_time`)     AS `total_remaining`,
                 SUM(CASE WHEN `status` = 'open'   THEN 1 ELSE 0 END) AS `open_count`,
                 SUM(CASE WHEN `status` = 'closed' THEN 1 ELSE 0 END) AS `closed_count`
               FROM `issues`
              WHERE `project_id` = ?
              GROUP BY COALESCE(`iteration`, '')"
        );
        $stmt->execute([$projectId]);

        return array_map(static function (array $row): array {
            return [
                'iteration'       => (string) $row['iteration'],
                'total_estimated' => (float)  $row['total_estimated'],
                'total_remaining' => (float)  $row['total_remaining'],
                'open_count'      => (int)    $row['open_count'],
                'closed_count'    => (int)    $row['closed_count'],
            ];
        }, $stmt->fetchAll());
    }

    /**
     * Return issues for a project with optional filters.
     *
     * Supported filter keys: assignee (string), iteration (string), status ('open'|'closed')
     *
     * @param  array<string,string> $filters
     * @return array<int, array<string,mixed>>
     */
    public function findByProject(int $projectId, array $filters = []): array
    {
        $where  = ['`project_id` = :project_id'];
        $params = ['project_id' => $projectId];

        if (isset($filters['assignee']) && $filters['assignee'] !== '') {
            $where[]              = '`assignee` = :assignee';
            $params['assignee']   = $filters['assignee'];
        }
        if (isset($filters['iteration']) && $filters['iteration'] !== '') {
            $where[]              = '`iteration` = :iteration';
            $params['iteration']  = $filters['iteration'];
        }
        if (isset($filters['status']) && in_array($filters['status'], ['open', 'closed'], true)) {
            $where[]             = '`status` = :status';
            $params['status']    = $filters['status'];
        }

        $sql  = 'SELECT * FROM `issues` WHERE ' . implode(' AND ', $where) . ' ORDER BY `updated_at` DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Return open and closed issue counts for a project.
     *
     * @return array{open: int, closed: int}
     */
    public function getCountsByProject(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 SUM(CASE WHEN `status` = 'open'   THEN 1 ELSE 0 END) AS `open`,
                 SUM(CASE WHEN `status` = 'closed' THEN 1 ELSE 0 END) AS `closed`
               FROM `issues`
              WHERE `project_id` = ?"
        );
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();

        return [
            'open'   => (int) ($row['open']   ?? 0),
            'closed' => (int) ($row['closed'] ?? 0),
        ];
    }

    /**
     * Fetch a single issue row by local id.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `issues` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Update time-tracking fields on an issue row.
     *
     * Only fields present in $fields are updated.
     * $fields keys: estimated_time, remaining_time, actual_time
     *
     * @param array<string, float> $fields
     */
    public function updateTimeFields(int $id, array $fields): void
    {
        $allowed = ['estimated_time', 'remaining_time', 'actual_time'];
        $sets    = [];
        $params  = [];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[]      = "`{$col}` = :{$col}";
                $params[$col] = $fields[$col];
            }
        }

        if (empty($sets)) {
            return;
        }

        $params['id'] = $id;
        $sql  = 'UPDATE `issues` SET ' . implode(', ', $sets) . ', `updated_at` = NOW() WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Aggregate estimation accuracy per member (closed issues only).
     *
     * @param  int         $projectId
     * @param  string|null $iteration  When provided, filters to that iteration.
     * @return array<int, array{assignee: string, total_estimated: float, total_actual: float, issues_count: int}>
     */
    public function aggregateEfficiencyByMember(int $projectId, ?string $iteration): array
    {
        $sql = "SELECT
                    `assignee`,
                    SUM(`estimated_time`) AS `total_estimated`,
                    SUM(`actual_time`)    AS `total_actual`,
                    COUNT(*)              AS `issues_count`
                  FROM `issues`
                 WHERE `project_id` = :project_id
                   AND `status`     = 'closed'
                   AND `assignee`   IS NOT NULL";

        $params = ['project_id' => $projectId];

        if ($iteration !== null) {
            $sql             .= ' AND `iteration` = :iteration';
            $params['iteration'] = $iteration;
        }

        $sql .= ' GROUP BY `assignee`';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Aggregate estimation accuracy per member AND iteration (closed issues).
     *
     * Used for trend data.
     *
     * @return array<int, array{assignee: string, iteration: string, total_estimated: float, total_actual: float, issues_count: int}>
     */
    public function aggregateEfficiencyByMemberAndIteration(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                 `assignee`,
                 COALESCE(`iteration`, '') AS `iteration`,
                 SUM(`estimated_time`)     AS `total_estimated`,
                 SUM(`actual_time`)        AS `total_actual`,
                 COUNT(*)                  AS `issues_count`
               FROM `issues`
              WHERE `project_id` = ?
                AND `status`     = 'closed'
                AND `assignee`   IS NOT NULL
              GROUP BY `assignee`, COALESCE(`iteration`, '')"
        );
        $stmt->execute([$projectId]);

        return $stmt->fetchAll();
    }
}

