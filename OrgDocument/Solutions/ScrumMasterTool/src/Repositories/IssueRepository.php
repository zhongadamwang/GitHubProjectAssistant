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
     * Aggregate time-tracking totals grouped by iteration for a project.
     *
     * Used by BurndownService::captureDaily() (T014) to populate burndown_daily.
     * Only rows where `iteration IS NOT NULL` are included.
     * COALESCE ensures NULL time fields (unset by team) contribute 0.00.
     *
     * @return array<int,array{
     *   iteration: string,
     *   total_estimated: float,
     *   total_remaining: float,
     *   open_count: int,
     *   closed_count: int,
     * }>
     */
    public function aggregateTimeByIteration(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                 `iteration`,
                 COALESCE(SUM(`estimated_time`), 0.0) AS total_estimated,
                 COALESCE(SUM(`remaining_time`), 0.0) AS total_remaining,
                 SUM(CASE WHEN `status` = \'open\'   THEN 1 ELSE 0 END) AS open_count,
                 SUM(CASE WHEN `status` = \'closed\' THEN 1 ELSE 0 END) AS closed_count
               FROM `issues`
              WHERE `project_id` = :project_id
                AND `iteration`  IS NOT NULL
              GROUP BY `iteration`'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    /**
     * Aggregate efficiency metrics per assignee for closed issues.
     *
     * Used by EfficiencyService::getMemberEfficiency() (T015).
     * Only closed issues contribute; open issues may have incomplete actual_time.
     * When $iteration is supplied the query is scoped to that sprint only.
     * COALESCE maps NULL time fields to 0.00.
     *
     * @return array<int,array{
     *   member: string,
     *   estimated: float,
     *   actual: float,
     *   issues_count: int,
     * }>
     */
    public function aggregateEfficiencyByMember(int $projectId, ?string $iteration = null): array
    {
        $sql = 'SELECT
                    `assignee`                              AS member,
                    COALESCE(SUM(`estimated_time`), 0.0)   AS estimated,
                    COALESCE(SUM(`actual_time`), 0.0)      AS actual,
                    COUNT(*)                               AS issues_count
                  FROM `issues`
                 WHERE `project_id` = :project_id
                   AND `status`     = \'closed\'
                   AND `assignee`   IS NOT NULL';

        $params = ['project_id' => $projectId];

        if ($iteration !== null) {
            $sql .= ' AND `iteration` = :iteration';
            $params['iteration'] = $iteration;
        }

        $sql .= ' GROUP BY `assignee`';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Aggregate efficiency metrics per assignee AND iteration for closed issues.
     *
     * Used by EfficiencyService::getMemberTrend() (T015) to build historical
     * accuracy ratio data.  Results are ordered by assignee ASC, iteration ASC.
     *
     * @return array<int,array{
     *   member: string,
     *   iteration: string,
     *   estimated: float,
     *   actual: float,
     *   issues_count: int,
     * }>
     */
    public function aggregateEfficiencyByMemberAndIteration(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                 `assignee`                              AS member,
                 `iteration`,
                 COALESCE(SUM(`estimated_time`), 0.0)   AS estimated,
                 COALESCE(SUM(`actual_time`), 0.0)      AS actual,
                 COUNT(*)                               AS issues_count
               FROM `issues`
              WHERE `project_id` = :project_id
                AND `status`     = \'closed\'
                AND `assignee`   IS NOT NULL
                AND `iteration`  IS NOT NULL
              GROUP BY `assignee`, `iteration`
              ORDER BY `assignee` ASC, `iteration` ASC'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll();
    }

    /**
     * Return issues for a project with optional filters.
     *
     * Supported filters (all optional):
     *   'assignee'  => string  — exact match on assignee login
     *   'iteration' => string  — exact match on iteration name
     *   'status'    => string  — 'open' or 'closed'
     *
     * Results are ordered by github_updated_at DESC.
     * All filter values are bound as parameters — no string interpolation.
     *
     * @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public function findByProject(int $projectId, array $filters = []): array
    {
        $conditions = ['`project_id` = :project_id'];
        $params     = ['project_id' => $projectId];

        if (isset($filters['assignee']) && $filters['assignee'] !== '') {
            $conditions[] = '`assignee` = :assignee';
            $params['assignee'] = $filters['assignee'];
        }

        if (isset($filters['iteration']) && $filters['iteration'] !== '') {
            $conditions[] = '`iteration` = :iteration';
            $params['iteration'] = $filters['iteration'];
        }

        if (isset($filters['status']) && in_array($filters['status'], ['open', 'closed'], true)) {
            $conditions[] = '`status` = :status';
            $params['status'] = $filters['status'];
        }

        $sql = 'SELECT `id`, `project_id`, `github_issue_id`, `title`, `status`,
                       `assignee`, `labels`, `iteration`,
                       `estimated_time`, `remaining_time`, `actual_time`,
                       `github_updated_at`, `created_at`, `updated_at`
                  FROM `issues`
                 WHERE ' . implode(' AND ', $conditions) . '
                 ORDER BY `github_updated_at` DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Return open and closed issue counts for a project.
     *
     * Used by ProjectController::getProject() to augment project detail.
     *
     * @return array{open: int, closed: int}
     */
    public function getCountsByProject(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                 SUM(CASE WHEN `status` = \'open\'   THEN 1 ELSE 0 END) AS open_count,
                 SUM(CASE WHEN `status` = \'closed\' THEN 1 ELSE 0 END) AS closed_count
               FROM `issues`
              WHERE `project_id` = :project_id'
        );
        $stmt->execute(['project_id' => $projectId]);
        $row = $stmt->fetch();

        return [
            'open'   => (int) ($row['open_count']   ?? 0),
            'closed' => (int) ($row['closed_count'] ?? 0),
        ];
    }

    /**
     * Return an issue row by its local auto-increment ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $issueId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `issues` WHERE `id` = :id LIMIT 1'
        );
        $stmt->execute(['id' => $issueId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
