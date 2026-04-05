<?php

declare(strict_types=1);

namespace App\Services;

/**
 * SyncResult — immutable value object returned by SyncService::run().
 *
 * Carries per-run statistics and a status string.
 * Status values: 'success' | 'partial' | 'failed'
 */
final class SyncResult
{
    public function __construct(
        public readonly string $status,
        public readonly int    $added,
        public readonly int    $updated,
        public readonly int    $unchanged,
        public readonly int    $errors,
        public readonly string $snapshotFile = '',
        public readonly string $errorMessage = '',
    ) {
    }

    public function toArray(): array
    {
        return [
            'status'        => $this->status,
            'issues_added'   => $this->added,
            'issues_updated' => $this->updated,
            'unchanged'     => $this->unchanged,
            'errors'        => $this->errors,
            'snapshot_file' => $this->snapshotFile,
            'error_message' => $this->errorMessage,
        ];
    }
}
