<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * TimeLogRepository — append-only audit trail for time field changes on issues.
 *
 * All writes use PDO prepared statements.
 * The time_logs table has a FK to issues(id) and users(id).
 */
final class TimeLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insert a single time-log audit entry.
     *
     * @param string $fieldName  One of: 'estimated_time', 'remaining_time', 'actual_time'
     */
    public function insert(int $issueId, int $userId, string $fieldName, float $oldValue, float $newValue): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `time_logs` (`issue_id`, `changed_by`, `field_name`, `old_value`, `new_value`)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$issueId, $userId, $fieldName, $oldValue, $newValue]);
    }

    /**
     * Return the most recent time-log entries for an issue, newest first.
     *
     * @return array<int, array<string,mixed>>
     */
    public function findByIssue(int $issueId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `time_logs`
              WHERE `issue_id` = ?
              ORDER BY `changed_at` DESC
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute([$issueId]);

        return $stmt->fetchAll();
    }
}
