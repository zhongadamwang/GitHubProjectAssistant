<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CredentialConfigurationException;
use App\Exceptions\CredentialDecryptionException;
use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;

/**
 * SyncOrchestrator — syncs tracked projects, each with its own account token and mapping.
 *
 * - runAll(): every tracked project sequentially (cron). One project's failure never stops
 *   the others. Before each project the remaining GitHub quota of its account (measured after
 *   the previous project of that account) is checked; below the floor the project is skipped
 *   with status `rate_limited`.
 * - syncProject(): one project (manual "Sync Now").
 * - Every outcome is recorded: sync_history (written by SyncService, or here when the run
 *   could not start), projects.last_sync_status/at, and the account status when GitHub
 *   rejects the token.
 * - Legacy fallback: a project that is not linked to an account is synced with the .env
 *   credentials (REPO_PAT/REPO_ORG/REPO_PROJECT_NUMBER) while those are still configured.
 *
 * Never throws for sync problems — they become a SyncOutcome status.
 */
final class SyncOrchestrator
{
    private bool $legacyNoticeLogged = false;

    /** Skip further projects of an account when fewer GraphQL points remain. */
    public const RATE_FLOOR = 200;

    /**
     * @param array{pat:string, org:string, project_number:int, graphql_url:string} $legacy settings.github
     */
    public function __construct(
        private readonly ProjectRepository       $projects,
        private readonly GitHubAccountRepository $accounts,
        private readonly SyncHistoryRepository   $history,
        private readonly SyncServiceFactory      $factory,
        private readonly AccountConnectionTester $tester,
        private readonly array                   $legacy = [],
        private readonly int                     $rateFloor = self::RATE_FLOOR,
    ) {
    }

    /**
     * Sync every tracked project.
     *
     * @return SyncOutcome[]
     */
    public function runAll(): array
    {
        $tracked = $this->projects->findTracked();

        if ($tracked === [] && $this->legacyConfigured()) {
            return [$this->runLegacyBootstrap()];
        }

        $remaining = [];
        $outcomes  = [];

        foreach ($tracked as $project) {
            $outcomes[] = $this->runProject($project, $remaining);
        }

        return $outcomes;
    }

    /**
     * Sync one project by id. Returns null when the project does not exist.
     */
    public function syncProject(int $projectId): ?SyncOutcome
    {
        $project = $this->projects->findById($projectId);

        if ($project === null) {
            return null;
        }

        $remaining = [];

        return $this->runProject($project, $remaining);
    }

    /**
     * @param array<string,mixed> $project
     * @param array<int,int>      $remaining GraphQL points left, per account id
     */
    private function runProject(array $project, array &$remaining): SyncOutcome
    {
        $id        = (int) $project['id'];
        $name      = (string) $project['name'];
        $accountId = $project['account_id'] !== null ? (int) $project['account_id'] : null;
        $account   = $accountId !== null ? $this->accounts->findWithSecret($accountId) : null;

        if ($account === null && !$this->legacyConfigured()) {
            return $this->finish($id, $name, 'needs_attention', 'This project is not linked to a GitHub account — an admin must configure one.', true);
        }

        if ($accountId !== null && isset($remaining[$accountId]) && $remaining[$accountId] < $this->rateFloor) {
            return $this->finish($id, $name, 'rate_limited', 'Skipped: the GitHub rate limit for this account is nearly exhausted.', true);
        }

        if ($account === null) {
            $this->noteLegacyUse();
        }

        try {
            $service = $account !== null
                ? $this->factory->forProject($project, $account)
                : $this->factory->forLegacy(
                    ['org' => (string) $project['github_owner'], 'project_number' => (int) $project['project_number']] + $this->legacy,
                    $id,
                    $project['field_mapping'],
                );

            $result = $service->run();

            if ($accountId !== null) {
                $this->rememberQuota($remaining, $accountId, $service->client());
                if (($account['status'] ?? '') !== 'ok') {
                    $this->accounts->updateStatus($accountId, 'ok', 'Last sync succeeded.', gmdate('Y-m-d H:i:s'));
                }
            }

            return $this->finish($id, $name, $result->status === 'success' ? 'ok' : 'partial', '', false, $result);
        } catch (CredentialConfigurationException) {
            return $this->finish($id, $name, 'needs_attention', 'APP_KEY is not configured on the server.', true);
        } catch (CredentialDecryptionException) {
            if ($accountId !== null) {
                $this->accounts->updateStatus($accountId, 'needs_reentry', 'The stored token cannot be decrypted — enter the token again.');
            }

            return $this->finish($id, $name, 'needs_attention', 'The account token cannot be decrypted — an admin must enter it again.', true);
        } catch (RateLimitException) {
            return $this->finish($id, $name, 'rate_limited', 'GitHub rate limit reached — the next run will retry.');
        } catch (GitHubApiException $e) {
            $class = $this->tester->classify($e);

            if (in_array($class['status'], ['invalid', 'expired', 'insufficient_scope'], true)) {
                if ($accountId !== null) {
                    $this->accounts->updateStatus($accountId, $class['status'], $class['message'], $class['verified_at']);
                }

                return $this->finish($id, $name, 'needs_attention', $class['message'], false);
            }

            return $this->finish($id, $name, 'failed', $class['message']);
        } catch (\Throwable $e) {
            error_log('SyncOrchestrator: unexpected ' . get_class($e) . ' while syncing project ' . $id);

            return $this->finish($id, $name, 'failed', 'Unexpected error during sync (see server log).', true);
        }
    }

    /**
     * First sync of a legacy install that has .env credentials but no project row yet.
     */
    private function runLegacyBootstrap(): SyncOutcome
    {
        $this->noteLegacyUse();

        $name = (string) $this->legacy['org'] . ' #' . (int) $this->legacy['project_number'];

        try {
            $result = $this->factory->forLegacy($this->legacy)->run();

            return new SyncOutcome(null, $name, $result->status === 'success' ? 'ok' : 'partial', 'Synced with legacy .env credentials.', $result);
        } catch (RateLimitException) {
            return new SyncOutcome(null, $name, 'rate_limited', 'GitHub rate limit reached — the next run will retry.');
        } catch (GitHubApiException $e) {
            $class = $this->tester->classify($e);

            return new SyncOutcome(null, $name, in_array($class['status'], ['invalid', 'expired', 'insufficient_scope'], true) ? 'needs_attention' : 'failed', $class['message']);
        } catch (\Throwable $e) {
            error_log('SyncOrchestrator: unexpected ' . get_class($e) . ' during legacy sync');

            return new SyncOutcome(null, $name, 'failed', 'Unexpected error during sync (see server log).');
        }
    }

    /**
     * Record the outcome on the project; when the run never started write the history row here.
     */
    private function finish(int $projectId, string $name, string $status, string $message, bool $writeHistory = false, ?SyncResult $result = null): SyncOutcome
    {
        if ($writeHistory) {
            try {
                $this->history->insert([
                    'project_id'    => $projectId,
                    'status'        => 'failed',
                    'error_message' => $message,
                ]);
            } catch (\Throwable) {
                // history is best effort
            }
        }

        try {
            $this->projects->recordSyncStatus($projectId, $status);
        } catch (\Throwable) {
            // status is best effort
        }

        return new SyncOutcome($projectId, $name, $status, $message, $result);
    }

    /**
     * @param array<int,int> $remaining
     */
    private function rememberQuota(array &$remaining, int $accountId, \App\Services\GitHubClientInterface $client): void
    {
        if ($client instanceof GitHubAccountClientInterface) {
            $points = $client->lastMeta()['rate_remaining'];
            if ($points !== null) {
                $remaining[$accountId] = $points;
            }
        }
    }

    /** Logged once per run: the .env credentials still work but should be imported and removed. */
    private function noteLegacyUse(): void
    {
        if ($this->legacyNoticeLogged) {
            return;
        }
        $this->legacyNoticeLogged = true;
        error_log('SyncOrchestrator: DEPRECATED — syncing with the legacy .env GitHub settings; run "php cron/import-env-account.php" and remove REPO_PAT from .env.');
    }

    private function legacyConfigured(): bool
    {
        return ($this->legacy['pat'] ?? '') !== '' && ($this->legacy['org'] ?? '') !== '' && (int) ($this->legacy['project_number'] ?? 0) > 0;
    }
}
