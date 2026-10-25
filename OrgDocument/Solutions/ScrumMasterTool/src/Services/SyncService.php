<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\GraphQL\ResponseParser;
use App\Models\Issue;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;
use App\Services\BurndownService;

/**
 * SyncService — orchestrates a complete GitHub Projects v2 sync cycle.
 *
 * Sync flow (ADR-4, ADR-5):
 *   1. Fetch project metadata via FETCH_PROJECT_FIELDS
 *   2. Fetch all project items via fetchAllProjectItems() (auto-paginated)
 *   3. Parse project + items into domain models
 *   4. Upsert project row (sets sync_timestamp)
 *   5. For each issue: insert when new; update when GitHub's updatedAt is newer OR the
 *      stored hours / iteration / status / assignee differ (editing a Projects field
 *      does not bump the Issue's updatedAt)
 *   6. Write timestamped JSON snapshot to data/snapshots/
 *   7. Write sync_history record (always — success or failure)
 *
 * Concurrency: lock-file guard belongs in the cron entry point (T011), not here.
 * Hours (estimated/remaining/actual) come from GitHub and overwrite local values;
 * changes to existing issues are audited in time_logs with a NULL (system) actor.
 */
final class SyncService
{
    private const HOUR_FIELDS = [
        'estimated_time' => 'estimatedHours',
        'remaining_time' => 'remainingHours',
        'actual_time'    => 'actualHours',
    ];

    /**
     * @param array<string,string> $fieldNames        Overrides for ResponseParser::DEFAULT_FIELD_NAMES
     * @param int|null             $projectId         Local projects.id when already known — failed runs are
     *                                                then attributed to the project in sync_history
     * @param int                  $snapshotRetention Keep at most this many snapshot files per directory (0 = keep all)
     */
    public function __construct(
        private readonly GitHubClientInterface $gitHub,
        private readonly ProjectRepository     $projectRepo,
        private readonly IssueRepository       $issueRepo,
        private readonly SyncHistoryRepository $historyRepo,
        private readonly BurndownService       $burndownService,
        private readonly string                $owner,
        private readonly int                   $projectNumber,
        private readonly string                $snapshotDir,
        private readonly ?TimeLogRepository    $timeLogRepo = null,
        private readonly array                 $fieldNames  = [],
        private readonly ?int                  $projectId   = null,
        private readonly int                   $snapshotRetention = 0,
    ) {
    }

    /**
     * The GitHub client used by this service (the orchestrator reads its rate-limit metadata).
     */
    public function client(): GitHubClientInterface
    {
        return $this->gitHub;
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
            $this->writeHistory($this->projectId, 'failed', 0, 0, '', $e->getMessage());
            throw $e;
        }

        // ------------------------------------------------------------------
        // Step 3 — Parse
        // ------------------------------------------------------------------
        $projectData = ($projectRaw['data']['organization'] ?? $projectRaw['data']['user'] ?? [])['projectV2'] ?? [];
        $project     = ResponseParser::parseProject($projectData, $this->owner);
        $issues      = ResponseParser::parseIssueNodes($rawNodes, $this->fieldNames);

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
        // Step 4b — Store the sprint (iteration) configuration; non-fatal
        // ------------------------------------------------------------------
        try {
            $this->syncIterations($projectId, $project->fields);
        } catch (\Throwable $e) {
            trigger_error('SyncService: iteration sync failed: ' . $e->getMessage(), E_USER_WARNING);
        }

        // ------------------------------------------------------------------
        // Step 5 — Diff & upsert issues
        // ------------------------------------------------------------------
        foreach ($issues as $issue) {
            try {
                $stored = $this->issueRepo->findByGitHubId($issue->contentId, $projectId);

                // Normalize GitHub ISO 8601 ('2026-04-01T10:00:00Z') to MySQL
                // DATETIME format ('2026-04-01 10:00:00') for comparison.
                $githubTs = $this->normalizeTimestamp($issue->updatedAt);

                if ($stored === null) {
                    // New issue — not yet in local DB
                    $this->issueRepo->upsertFromGitHub($issue, $projectId);
                    $added++;
                } elseif (
                    ($githubTs !== null && $githubTs > ($stored['github_updated_at'] ?? ''))
                    || $this->differsFromStored($issue, $stored)
                ) {
                    // GitHub has a newer version, or a Projects field changed
                    $this->issueRepo->upsertFromGitHub($issue, $projectId);
                    $this->auditHourChanges($issue, $stored);
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
     * Persist the project's iteration field configuration and mark the sprint
     * running today as projects.current_iteration.
     *
     * @param array<int,array<string,mixed>> $fields  Project::$fields definitions
     */
    private function syncIterations(int $projectId, array $fields): void
    {
        $wanted = strtolower($this->fieldNames['iteration'] ?? ResponseParser::DEFAULT_FIELD_NAMES['iteration']);

        $iterations = [];
        foreach ($fields as $field) {
            if (strtolower((string) ($field['name'] ?? '')) === $wanted) {
                $iterations = $field['iterations'] ?? [];
                break;
            }
        }

        if (empty($iterations)) {
            return;
        }

        $this->projectRepo->syncIterations($projectId, $iterations);

        $today   = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $current = null;

        foreach ($iterations as $it) {
            if (empty($it['startDate']) || empty($it['title'])) {
                continue;
            }

            $start = new \DateTimeImmutable((string) $it['startDate'], new \DateTimeZone('UTC'));
            $end   = $start->modify('+' . (int) ($it['duration'] ?? 0) . ' days');

            if ($start <= $today && $today < $end) {
                $current = (string) $it['title'];
                break;
            }
        }

        $this->projectRepo->updateCurrentIteration($projectId, $current);
    }

    /**
     * True when the GitHub values for hours, iteration, status or assignee differ
     * from the stored row. Needed because editing a Projects field value does not
     * change the underlying Issue's updatedAt.
     *
     * @param array<string,mixed> $stored  Row from `issues`
     */
    private function differsFromStored(Issue $issue, array $stored): bool
    {
        foreach (self::HOUR_FIELDS as $column => $property) {
            if (abs((float) ($issue->$property ?? 0.0) - (float) ($stored[$column] ?? 0.0)) >= 0.005) {
                return true;
            }
        }

        $iteration = $issue->customFields['iteration'] ?? null;

        return (is_string($iteration) ? $iteration : null) !== ($stored['iteration'] ?? null)
            || strtolower($issue->state) !== ($stored['status'] ?? null)
            || ($issue->assignees[0]['login'] ?? null) !== ($stored['assignee'] ?? null);
    }

    /**
     * Record changed hour fields of an existing issue in time_logs (system actor).
     *
     * @param array<string,mixed> $stored  Row from `issues` before the upsert
     */
    private function auditHourChanges(Issue $issue, array $stored): void
    {
        if ($this->timeLogRepo === null) {
            return;
        }

        foreach (self::HOUR_FIELDS as $column => $property) {
            $old = (float) ($stored[$column] ?? 0.0);
            $new = (float) ($issue->$property ?? 0.0);

            if (abs($new - $old) >= 0.005) {
                $this->timeLogRepo->insert((int) $stored['id'], null, $column, $old, $new);
            }
        }
    }

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

        // Seconds in the name keep two runs within the same minute from overwriting each other
        $filename = gmdate('Y-m-d_H-i-s') . '.json';
        $path     = $this->snapshotDir . DIRECTORY_SEPARATOR . $filename;

        $payload = json_encode(
            ['project' => $projectData, 'items' => $rawNodes],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );

        file_put_contents($path, $payload, LOCK_EX);
        $this->pruneSnapshots();

        return $path;
    }

    /**
     * Delete the oldest snapshot files beyond the retention count (names sort chronologically).
     */
    private function pruneSnapshots(): void
    {
        if ($this->snapshotRetention < 1) {
            return;
        }

        $files = glob($this->snapshotDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
        sort($files);

        foreach (array_slice($files, 0, max(0, count($files) - $this->snapshotRetention)) as $old) {
            @unlink($old);
        }
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
                'error_message'  => $errorMessage !== '' ? SecretRedactor::redact($errorMessage) : null,
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
