<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\IssueRepository;
use App\Repositories\TimeLogRepository;
use PDO;

/**
 * TimeTrackingService — updates time fields on issues with full audit logging.
 *
 * Security / integrity rules:
 *  - Only provided fields are updated (partial update)
 *  - Each field value must be in [0, 9999.99]
 *  - Read → log → write wrapped in a single PDO transaction
 *  - Log entries are inserted BEFORE the issue row update so a failed
 *    update triggers a rollback and no orphan logs are written
 *
 * field_name ENUM: 'estimated_time' | 'remaining_time' | 'actual_time'
 */
final class TimeTrackingService
{
    private const ALLOWED_FIELDS = ['estimated_time', 'remaining_time', 'actual_time'];
    private const MAX_VALUE      = 9999.99;

    public function __construct(
        private readonly TimeLogRepository $logRepo,
        private readonly IssueRepository   $issueRepo,
        private readonly PDO               $pdo,
    ) {
    }

    /**
     * Update time-tracking fields on an issue with full audit trail.
     *
     * @param int                  $issueId    Local issues.id
     * @param int                  $changedBy  users.id of the actor
     * @param array<string, mixed> $fields     Only keys in ALLOWED_FIELDS are processed
     *
     * @throws \InvalidArgumentException  On validation failure (invalid field name or value)
     * @throws \RuntimeException          When the issue does not exist
     */
    public function updateTime(int $issueId, int $changedBy, array $fields): void
    {
        $filtered = $this->validateFields($fields);

        if (empty($filtered)) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $current = $this->issueRepo->findById($issueId);

            if ($current === null) {
                throw new \RuntimeException("Issue {$issueId} not found.");
            }

            // Insert one time_log row per changed field
            foreach ($filtered as $fieldName => $newValue) {
                $oldValue = (float) ($current[$fieldName] ?? 0.0);
                $this->logRepo->insert($issueId, $changedBy, $fieldName, $oldValue, $newValue);
            }

            // Update the issue row
            $this->issueRepo->updateTimeFields($issueId, $filtered);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Validate and filter the supplied fields map.
     *
     * @param  array<string, mixed> $fields
     * @return array<string, float>
     * @throws \InvalidArgumentException
     */
    private function validateFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $name => $value) {
            if (!in_array($name, self::ALLOWED_FIELDS, true)) {
                // Silently ignore unrecognised keys — only log valid fields
                continue;
            }

            if (!is_numeric($value)) {
                throw new \InvalidArgumentException(
                    "Field '{$name}' must be a numeric value; got: " . gettype($value)
                );
            }

            $floatValue = (float) $value;

            if ($floatValue < 0.0) {
                throw new \InvalidArgumentException(
                    "Field '{$name}' must be >= 0; got: {$floatValue}"
                );
            }

            if ($floatValue > self::MAX_VALUE) {
                throw new \InvalidArgumentException(
                    "Field '{$name}' must be <= " . self::MAX_VALUE . "; got: {$floatValue}"
                );
            }

            $result[$name] = $floatValue;
        }

        return $result;
    }
}
