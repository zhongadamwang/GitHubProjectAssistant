<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TimeLogRepository;
use PDO;

/**
 * TimeTrackingService — updates issue time fields with full audit trail.
 *
 * Only the three local time fields are written:
 *   estimated_time, remaining_time, actual_time
 *
 * The sync process NEVER writes these columns; they are exclusively managed
 * here (see IssueRepository::upsertFromGitHub — columns excluded from UPDATE).
 *
 * Security:
 *   - Input validated before any DB write (non-negative, max 9999.99).
 *   - Unknown field names are rejected to prevent ENUM violations.
 *   - Read + log + update wrapped in a single PDO transaction.
 *
 * Source Requirements: R-004, R-006
 */
final class TimeTrackingService
{
    /** Fields permitted for update. Must match the time_logs.field_name ENUM. */
    private const ALLOWED_FIELDS = ['estimated_time', 'remaining_time', 'actual_time'];

    /** Maximum value accepted for any time field (matches DECIMAL(8,2)). */
    private const MAX_VALUE = 9999.99;

    public function __construct(
        private readonly TimeLogRepository $timeLogRepo,
        private readonly PDO               $pdo,
    ) {
    }

    /**
     * Update one or more time fields on an issue.
     *
     * Only keys present in $fields are updated; absent keys are left
     * unchanged.  For each changed field an audit row is inserted into
     * time_logs with the old and new value.
     *
     * The entire operation (SELECT current values → INSERT logs → UPDATE issue)
     * runs inside a single serialisable PDO transaction so no partial writes
     * can occur.
     *
     * @param int                                        $issueId   Local issues.id
     * @param int                                        $changedBy Authenticated user's users.id
     * @param array<string,float|int|string|null>        $fields    Partial map of field → value
     *
     * @throws \InvalidArgumentException  When a value is negative or > 9999.99
     * @throws \PDOException              On any database error (triggers rollback)
     */
    public function updateTime(int $issueId, int $changedBy, array $fields): void
    {
        // Keep only the three permitted keys
        $fields = array_intersect_key($fields, array_flip(self::ALLOWED_FIELDS));

        if (empty($fields)) {
            // Nothing to do — no recognised fields provided
            return;
        }

        // Validate before touching the database
        foreach ($fields as $name => $value) {
            $floatVal = (float) $value;
            if ($floatVal < 0.0 || $floatVal > self::MAX_VALUE) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Invalid value for '%s': %.2f. Must be between 0 and %.2f.",
                        $name,
                        $floatVal,
                        self::MAX_VALUE,
                    )
                );
            }
        }

        $this->pdo->beginTransaction();

        try {
            // Step 1 — read current values
            $currentRow = $this->fetchCurrentValues($issueId);

            // Step 2 — insert one time_logs row per changed field
            foreach ($fields as $name => $value) {
                $floatVal = (float) $value;
                $oldVal   = isset($currentRow[$name]) ? (float) $currentRow[$name] : 0.0;

                $this->timeLogRepo->insert($issueId, $changedBy, $name, $oldVal, $floatVal);
            }

            // Step 3 — build SET clause and update issues row
            $setClauses = [];
            $params     = [];

            foreach ($fields as $name => $value) {
                $setClauses[] = "`{$name}` = :{$name}";
                $params[$name] = (float) $value;
            }

            $setClauses[] = '`updated_at` = NOW()';
            $params['issue_id'] = $issueId;

            $stmt = $this->pdo->prepare(
                'UPDATE `issues` SET ' . implode(', ', $setClauses) . ' WHERE `id` = :issue_id'
            );
            $stmt->execute($params);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Fetch the current time-field values for an issue row.
     *
     * Returns an assoc array with keys estimated_time, remaining_time, actual_time.
     * Throws \RuntimeException when the issue ID does not exist.
     *
     * @return array<string,mixed>
     */
    private function fetchCurrentValues(int $issueId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `estimated_time`, `remaining_time`, `actual_time`
               FROM `issues`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $issueId]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw new \RuntimeException("Issue {$issueId} not found.");
        }

        return $row;
    }
}
