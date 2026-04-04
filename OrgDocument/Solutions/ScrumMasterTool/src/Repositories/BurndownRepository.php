<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * BurndownRepository — all database access for the `burndown_daily` table.
 *
 * burndown_daily schema (migration 006):
 *   id               INT UNSIGNED  AUTO_INCREMENT
 *   project_id       INT UNSIGNED  NOT NULL  (FK → projects.id)
 *   iteration        VARCHAR(100)  NOT NULL
 *   snapshot_date    DATE          NOT NULL
 *   total_estimated  DECIMAL(8,2)  NOT NULL DEFAULT 0.00
 *   ideal_remaining  DECIMAL(8,2)  NOT NULL DEFAULT 0.00
 *   actual_remaining DECIMAL(8,2)  NOT NULL DEFAULT 0.00
 *   open_count       INT UNSIGNED  NOT NULL DEFAULT 0
 *   closed_count     INT UNSIGNED  NOT NULL DEFAULT 0
 *   created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
 *   UNIQUE KEY (project_id, iteration, snapshot_date)
 */
final class BurndownRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Return all burndown_daily rows for a given project + iteration,
     * ordered ascending by snapshot_date.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getPointsForIteration(int $projectId, string $iteration): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `snapshot_date`, `total_estimated`,
                    `ideal_remaining`, `actual_remaining`,
                    `open_count`, `closed_count`
               FROM `burndown_daily`
              WHERE `project_id` = :project_id
                AND `iteration`  = :iteration
              ORDER BY `snapshot_date` ASC'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'iteration'  => $iteration,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Return the name of the most recently snapshotted iteration for a project.
     * Returns null when no burndown_daily rows exist for the project yet.
     */
    public function getLatestIteration(int $projectId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT `iteration`
               FROM `burndown_daily`
              WHERE `project_id` = :project_id
              ORDER BY `snapshot_date` DESC
              LIMIT 1'
        );
        $stmt->execute(['project_id' => $projectId]);

        $row = $stmt->fetch();

        return $row !== false ? (string) $row['iteration'] : null;
    }

    /**
     * Upsert a single daily burndown snapshot row.
     *
     * The UNIQUE KEY on (project_id, iteration, snapshot_date) ensures that
     * running the snapshot twice on the same day simply overwrites the row.
     *
     * Required keys in $data:
     *   iteration, snapshot_date, total_estimated, ideal_remaining,
     *   actual_remaining, open_count, closed_count
     *
     * @param array<string,mixed> $data
     */
    public function upsertDailySnapshot(int $projectId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `burndown_daily`
                 (`project_id`, `iteration`, `snapshot_date`,
                  `total_estimated`, `ideal_remaining`, `actual_remaining`,
                  `open_count`, `closed_count`)
             VALUES
                 (:project_id, :iteration, :snapshot_date,
                  :total_estimated, :ideal_remaining, :actual_remaining,
                  :open_count, :closed_count)
             ON DUPLICATE KEY UPDATE
                  `total_estimated`  = VALUES(`total_estimated`),
                  `ideal_remaining`  = VALUES(`ideal_remaining`),
                  `actual_remaining` = VALUES(`actual_remaining`),
                  `open_count`       = VALUES(`open_count`),
                  `closed_count`     = VALUES(`closed_count`)'
        );

        $stmt->execute([
            'project_id'       => $projectId,
            'iteration'        => $data['iteration'],
            'snapshot_date'    => $data['snapshot_date'],
            'total_estimated'  => $data['total_estimated']  ?? 0.0,
            'ideal_remaining'  => $data['ideal_remaining']  ?? 0.0,
            'actual_remaining' => $data['actual_remaining'] ?? 0.0,
            'open_count'       => $data['open_count']       ?? 0,
            'closed_count'     => $data['closed_count']     ?? 0,
        ]);
    }
}
