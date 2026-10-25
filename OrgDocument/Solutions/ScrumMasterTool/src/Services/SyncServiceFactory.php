<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;

/**
 * SyncServiceFactory — builds a SyncService for one tracked project:
 * the account's GitHub client (decrypted token), the project's owner/number, its
 * effective field mapping (project override → global defaults) and its own snapshot folder.
 */
final class SyncServiceFactory
{
    /**
     * @param array<string,string> $globalFieldNames settings.github.fields
     */
    public function __construct(
        private readonly ProjectRepository            $projects,
        private readonly IssueRepository              $issues,
        private readonly SyncHistoryRepository        $history,
        private readonly BurndownService              $burndown,
        private readonly TimeLogRepository            $timeLogs,
        private readonly GitHubClientFactoryInterface $clients,
        private readonly array                        $globalFieldNames,
        private readonly string                       $snapshotRoot,
        private readonly int                          $snapshotRetention,
    ) {
    }

    /**
     * @param array<string,mixed> $project  Row from ProjectRepository
     * @param array<string,mixed> $account  Row from GitHubAccountRepository::findWithSecret()
     *
     * @throws \App\Exceptions\CredentialConfigurationException
     * @throws \App\Exceptions\CredentialDecryptionException
     */
    public function forProject(array $project, array $account): SyncService
    {
        return $this->build(
            $this->clients->forAccount($account),
            (string) $project['github_owner'],
            (int) $project['project_number'],
            FieldMapping::resolve($this->globalFieldNames, $project['field_mapping'] ?? null),
            (int) $project['id'],
            $this->snapshotRoot . DIRECTORY_SEPARATOR . $project['id'],
        );
    }

    /**
     * Legacy path: credentials straight from .env (before an account is configured).
     *
     * @param array{pat:string, org:string, project_number:int, graphql_url:string} $legacy
     * @param array<string,string>|null $fieldMapping per-project overrides
     */
    public function forLegacy(array $legacy, ?int $projectId = null, ?array $fieldMapping = null): SyncService
    {
        return $this->build(
            $this->clients->forToken($legacy['pat'], $legacy['graphql_url'], 'organization'),
            $legacy['org'],
            (int) $legacy['project_number'],
            FieldMapping::resolve($this->globalFieldNames, $fieldMapping),
            $projectId,
            $projectId !== null ? $this->snapshotRoot . DIRECTORY_SEPARATOR . $projectId : $this->snapshotRoot,
        );
    }

    /**
     * @param array<string,string> $fieldNames
     */
    private function build(GitHubAccountClientInterface $client, string $owner, int $number, array $fieldNames, ?int $projectId, string $snapshotDir): SyncService
    {
        return new SyncService(
            gitHub:            $client,
            projectRepo:       $this->projects,
            issueRepo:         $this->issues,
            historyRepo:       $this->history,
            burndownService:   $this->burndown,
            owner:             $owner,
            projectNumber:     $number,
            snapshotDir:       $snapshotDir,
            timeLogRepo:       $this->timeLogs,
            fieldNames:        $fieldNames,
            projectId:         $projectId,
            snapshotRetention: $this->snapshotRetention,
        );
    }
}
