<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * BurndownRepository — database access for the `burndown_daily` table.
 *
 * All queries use PDO prepared statements.
 */
final class BurndownRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Return all daily snapshot rows for a given project + iteration,
     * ordered by snapshot_date ASC.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getPointsForIteration(int $projectId, string $iteration): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `snapshot_date`, `total_estimated`, `ideal_remaining`, `actual_remaining`
               FROM `burndown_daily`
              WHERE `project_id` = ? AND `iteration` = ?
              ORDER BY `snapshot_date` ASC'
        );
        $stmt->execute([$projectId, $iteration]);

        return $stmt->fetchAll();
    }

    /**
     * Return the most recent iteration name for a project, or null when
     * burndown_daily has no rows for that project.
     */
    public function getLatestIteration(int $projectId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT `iteration`
               FROM `burndown_daily`
              WHERE `project_id` = ?
              ORDER BY `snapshot_date` DESC
              LIMIT 1'
        );
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();

        return $row !== false ? (string) $row['iteration'] : null;
    }

    /**
     * Upsert a single daily snapshot row for (project_id, iteration, snapshot_date = CURDATE()).
     *
     * Idempotent: calling twice on the same calendar day overwrites the row cleanly.
     *
     * @param array{total_estimated: float, actual_remaining: float, ideal_remaining: float, open_count: int, closed_count: int} $data
     */
    public function upsertDailySnapshot(int $projectId, string $iteration, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `burndown_daily`
                 (`project_id`, `iteration`, `snapshot_date`,
                  `total_estimated`, `ideal_remaining`, `actual_remaining`,
                  `open_count`, `closed_count`)
             VALUES
                 (?, ?, CURDATE(), ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 `total_estimated`  = VALUES(`total_estimated`),
                 `ideal_remaining`  = VALUES(`ideal_remaining`),
                 `actual_remaining` = VALUES(`actual_remaining`),
                 `open_count`       = VALUES(`open_count`),
                 `closed_count`     = VALUES(`closed_count`)'
        );

        $stmt->execute([
            $projectId,
            $iteration,
            $data['total_estimated'],
            $data['ideal_remaining'],
            $data['actual_remaining'],
            $data['open_count'],
            $data['closed_count'],
        ]);
    }
}
