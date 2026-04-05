<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\GraphQL\ResponseParser;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Services\BurndownService;

/**
 * SyncService — orchestrates a complete GitHub Projects v2 sync cycle.
 *
 * Sync flow (ADR-4, ADR-5):
 *   1. Fetch project metadata via FETCH_PROJECT_FIELDS
 *   2. Fetch all project items via fetchAllProjectItems() (auto-paginated)
 *   3. Parse project + items into domain models
 *   4. Upsert project row (sets sync_timestamp)
 *   5. For each issue: diff against stored `github_updated_at` → upsert if newer or new
 *   6. Write timestamped JSON snapshot to data/snapshots/
 *   7. Write sync_history record (always — success or failure)
 *
 * Concurrency: lock-file guard belongs in the cron entry point (T011), not here.
 * Local time fields (estimated/remaining/actual hours) are NEVER overwritten.
 */
final class SyncService
{
    public function __construct(
        private readonly GitHubClientInterface $gitHub,
        private readonly ProjectRepository     $projectRepo,
        private readonly IssueRepository       $issueRepo,
        private readonly SyncHistoryRepository $historyRepo,
        private readonly BurndownService       $burndownService,
        private readonly string                $owner,
        private readonly int                   $projectNumber,
        private readonly string                $snapshotDir,
    ) {
    }

    /**
     * Execute one full sync cycle and return a SyncResult.
     *
     * On GitHub API failure the error is logged to sync_history (status=failed)
     * and the exception is rethrown so the cron entry point can log it.
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    public function run(): SyncResult
    {
        $added     = 0;
        $updated   = 0;
        $unchanged = 0;
        $errors    = 0;
        $snapshot  = '';

        // ------------------------------------------------------------------
        // Step 1 & 2 — Fetch from GitHub (may throw)
        // ------------------------------------------------------------------
        try {
            $projectRaw = $this->gitHub->query('FETCH_PROJECT_FIELDS', [
                'owner'  => $this->owner,
                'number' => $this->projectNumber,
            ]);

            $rawNodes = $this->gitHub->fetchAllProjectItems(
                $this->owner,
                $this->projectNumber,
            );
        } catch (GitHubApiException | RateLimitException $e) {
            $this->writeHistory(null, 'failed', 0, 0, '', $e->getMessage());
            throw $e;
        }

        // ------------------------------------------------------------------
        // Step 3 — Parse
        // ------------------------------------------------------------------
        $projectData = $projectRaw['data']['user']['projectV2'] ?? [];
        $project     = ResponseParser::parseProject($projectData, $this->owner);
        $issues      = ResponseParser::parseIssueNodes($rawNodes);

        // ------------------------------------------------------------------
        // Step 4 — Upsert project row; resolve local project ID
        // ------------------------------------------------------------------
        $this->projectRepo->upsertFromGitHub($project);
        $projectId = $this->projectRepo->getLocalId($project->githubId);

        if ($projectId === null) {
            // Should never happen — upsert just ran — but guard defensively
            $this->writeHistory(null, 'failed', 0, 0, '', 'Could not resolve local project ID after upsert');
            throw new \RuntimeException('Could not resolve local project ID after upsert');
        }

        // ------------------------------------------------------------------
        // Step 5 — Diff & upsert issues
        // ------------------------------------------------------------------
        foreach ($issues as $issue) {
            try {
                $storedUpdatedAt = $this->issueRepo->getStoredUpdatedAt($issue->contentId, $projectId);

                // Normalize GitHub ISO 8601 ('2026-04-01T10:00:00Z') to MySQL
                // DATETIME format ('2026-04-01 10:00:00') for comparison.
                $githubTs = $this->normalizeTimestamp($issue->updatedAt);

                if ($storedUpdatedAt === null) {
                    // New issue — not yet in local DB
                    $this->issueRepo->upsertFromGitHub($issue, $projectId);
                    $added++;
                } elseif ($githubTs !== null && $githubTs > $storedUpdatedAt) {
                    // GitHub has a newer version
                    $this->issueRepo->upsertFromGitHub($issue, $projectId);
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (\Throwable $e) {
                // Log per-issue errors without aborting the whole sync
                trigger_error(
                    sprintf(
                        'SyncService: failed to upsert issue %s (%s): %s',
                        $issue->contentId,
                        $issue->title,
                        $e->getMessage(),
                    ),
                    E_USER_WARNING,
                );
                $errors++;
            }
        }

        // ------------------------------------------------------------------
        // Step 6 — Write snapshot
        // ------------------------------------------------------------------
        try {
            $snapshot = $this->writeSnapshot($projectData, $rawNodes);
        } catch (\Throwable $e) {
            // Snapshot failure is non-fatal — sync still succeeded
            trigger_error('SyncService: snapshot write failed: ' . $e->getMessage(), E_USER_WARNING);
        }

        // ------------------------------------------------------------------
        // Step 6b — Capture daily burndown snapshot (T014)
        // ------------------------------------------------------------------
        try {
            $this->burndownService->captureDaily($projectId);
        } catch (\Throwable $e) {
            // burndown capture failure is non-fatal — never abort the sync
            trigger_error('SyncService: burndown captureDaily failed: ' . $e->getMessage(), E_USER_WARNING);
        }

        // ------------------------------------------------------------------
        // Step 7 — Write sync_history
        // ------------------------------------------------------------------
        $status = $errors === 0 ? 'success' : 'partial';
        $this->writeHistory($projectId, $status, $added, $updated, $snapshot, '');

        return new SyncResult(
            status:       $status,
            added:        $added,
            updated:      $updated,
            unchanged:    $unchanged,
            errors:       $errors,
            snapshotFile: $snapshot,
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Write a timestamped JSON snapshot file to the snapshot directory.
     * Directory is auto-created on first run.
     *
     * @param  array<string,mixed>      $projectData Raw project node
     * @param  array<int,array<string,mixed>> $rawNodes Raw item nodes
     * @return string  Absolute path of the written snapshot file
     */
    private function writeSnapshot(array $projectData, array $rawNodes): string
    {
        if (!is_dir($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0755, recursive: true);
        }

        $filename = gmdate('Y-m-d_H-i') . '.json';
        $path     = $this->snapshotDir . DIRECTORY_SEPARATOR . $filename;

        $payload = json_encode(
            ['project' => $projectData, 'items' => $rawNodes],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );

        file_put_contents($path, $payload, LOCK_EX);

        return $path;
    }

    /**
     * Write a record to sync_history regardless of success or failure.
     */
    private function writeHistory(
        ?int   $projectId,
        string $status,
        int    $added,
        int    $updated,
        string $snapshotFile,
        string $errorMessage,
    ): void {
        try {
            $this->historyRepo->insert([
                'project_id'     => $projectId,
                'status'         => $status,
                'issues_added'   => $added,
                'issues_updated' => $updated,
                'snapshot_file'  => $snapshotFile ?: null,
                'error_message'  => $errorMessage ?: null,
            ]);
        } catch (\Throwable $e) {
            // Never let history write failures mask the original sync result
            trigger_error('SyncService: sync_history write failed: ' . $e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * Normalise a GitHub ISO 8601 timestamp to MySQL DATETIME format.
     *
     * '2026-04-01T10:00:00Z' → '2026-04-01 10:00:00'
     * Already-normalised values pass through unchanged.
     * null → null
     */
    private function normalizeTimestamp(?string $ts): ?string
    {
        if ($ts === null) {
            return null;
        }

        return rtrim(str_replace('T', ' ', $ts), 'Z');
    }
}
