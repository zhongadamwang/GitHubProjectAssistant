<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * TimeLogRepository — append-only audit trail for issue time-field changes.
 *
 * time_logs schema (migration 004):
 *   id          INT UNSIGNED  AUTO_INCREMENT
 *   issue_id    INT UNSIGNED  NOT NULL  (FK → issues.id)
 *   changed_by  INT UNSIGNED  NOT NULL  (FK → users.id)
 *   field_name  ENUM('estimated_time','remaining_time','actual_time')
 *   old_value   DECIMAL(8,2)
 *   new_value   DECIMAL(8,2)
 *   changed_at  DATETIME  DEFAULT CURRENT_TIMESTAMP
 */
class TimeLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Append one audit log entry.
     *
     * @param int    $issueId    Local issues.id
     * @param int    $userId     Authenticated user's users.id
     * @param string $fieldName  One of 'estimated_time', 'remaining_time', 'actual_time'
     * @param float  $oldValue   Value before the change
     * @param float  $newValue   Value after the change
     */
    public function insert(
        int    $issueId,
        int    $userId,
        string $fieldName,
        float  $oldValue,
        float  $newValue,
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `time_logs`
                 (`issue_id`, `changed_by`, `field_name`, `old_value`, `new_value`)
             VALUES
                 (:issue_id, :changed_by, :field_name, :old_value, :new_value)'
        );

        $stmt->execute([
            'issue_id'   => $issueId,
            'changed_by' => $userId,
            'field_name' => $fieldName,
            'old_value'  => $oldValue,
            'new_value'  => $newValue,
        ]);
    }

    /**
     * Return the most recent audit log entries for an issue, newest first.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByIssue(int $issueId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `issue_id`, `changed_by`, `field_name`,
                    `old_value`, `new_value`, `changed_at`
               FROM `time_logs`
              WHERE `issue_id` = :issue_id
              ORDER BY `changed_at` DESC, `id` DESC
              LIMIT :limit'
        );

        $stmt->bindValue('issue_id', $issueId, PDO::PARAM_INT);
        $stmt->bindValue('limit',    $limit,   PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
