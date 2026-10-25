<?php

declare(strict_types=1);

namespace App\Services;

/**
 * SyncOutcome — result of syncing one project (never throws; failures become a status).
 *
 * Statuses:
 *   ok               sync finished without issue errors
 *   partial          sync finished, some issues could not be stored
 *   failed           GitHub/transport failure — the next run retries
 *   rate_limited     GitHub quota (nearly) exhausted — skipped or aborted
 *   needs_attention  an admin must act (token invalid/expired/undecryptable, APP_KEY missing, no account)
 */
final class SyncOutcome
{
    public function __construct(
        public readonly ?int         $projectId,
        public readonly string       $name,
        public readonly string       $status,
        public readonly string       $message = '',
        public readonly ?SyncResult  $result = null,
    ) {
    }

    /** True for statuses that count as a successful run. */
    public function isSuccess(): bool
    {
        return in_array($this->status, ['ok', 'partial'], true);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $payload = ['project_id' => $this->projectId, 'name' => $this->name, 'status' => $this->status, 'message' => $this->message]
            + ($this->result !== null ? $this->result->toArray() : []);

        // Never expose the server's directory layout through the API
        if (!empty($payload['snapshot_file'])) {
            $payload['snapshot_file'] = basename((string) $payload['snapshot_file']);
        }

        return $payload;
    }
}
