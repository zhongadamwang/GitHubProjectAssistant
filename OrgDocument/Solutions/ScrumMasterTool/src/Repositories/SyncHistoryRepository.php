<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * SyncHistoryRepository — all database access for the `sync_history` table.
 *
 * Every sync run (success or failure) must write a record so operators can
 * audit what happened and when.
 */
final class SyncHistoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insert a sync history record.
     *
     * Required keys in $record:
     *   project_id      int
     *   status          'success'|'partial'|'failed'
     *   issues_added    int
     *   issues_updated  int
     *
     * Optional keys (default to 0 / null):
     *   issues_removed      int
     *   graphql_points_used int
     *   snapshot_file       string|null
     *   error_message       string|null
     *
     * @param array<string,mixed> $record
     */
    public function insert(array $record): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `sync_history`
                 (`project_id`, `synced_at`, `status`,
                  `issues_added`, `issues_updated`, `issues_removed`,
                  `graphql_points_used`, `snapshot_file`, `error_message`)
             VALUES
                 (:project_id, :synced_at, :status,
                  :issues_added, :issues_updated, :issues_removed,
                  :graphql_points_used, :snapshot_file, :error_message)'
        );

        $stmt->execute([
            'project_id'          => $record['project_id'],
            'synced_at'           => gmdate('Y-m-d H:i:s'),
            'status'              => $record['status'],
            'issues_added'        => $record['issues_added']        ?? 0,
            'issues_updated'      => $record['issues_updated']      ?? 0,
            'issues_removed'      => $record['issues_removed']      ?? 0,
            'graphql_points_used' => $record['graphql_points_used'] ?? 0,
            'snapshot_file'       => $record['snapshot_file']       ?? null,
            'error_message'       => $record['error_message']       ?? null,
        ]);
    }

    /**
     * Return the most recent sync history records for a project, newest first.
     *
     * @param  int  $projectId  Local `projects.id`
     * @param  int  $limit
     * @return array<int,array<string,mixed>>
     */
    public function findLatest(int $projectId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `sync_history`
              WHERE `project_id` = ?
              ORDER BY `synced_at` DESC
              LIMIT ?'
        );
        // PDO requires explicit int binding for LIMIT with EMULATE_PREPARES=false
        $stmt->bindValue(1, $projectId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,     PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Return the most recent sync history record for a project, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findLastSync(int $projectId): ?array
    {
        $rows = $this->findLatest($projectId, 1);
        return $rows[0] ?? null;
    }
}
