<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;

/**
 * EnvAccountImporter — moves the legacy .env GitHub settings (REPO_ORG, REPO_PAT,
 * REPO_PROJECT_NUMBER, REPO_GRAPHQL_URL) into a stored, encrypted account and a tracked project.
 *
 * Idempotent: running it again never creates a second account, never overwrites a stored token
 * and never changes a project that is already linked to an account. Existing project rows keep
 * their id, so issues, burndown, history and iterations stay attached.
 *
 * Result `status`:
 *   imported         — an account was created and/or a project was linked or tracked
 *   already_imported — nothing to do
 *   not_configured   — a legacy value is missing (see `missing`)
 *   app_key_missing  — APP_KEY is not set, tokens cannot be stored
 */
final class EnvAccountImporter
{
    /**
     * @param array{pat:string, org:string, project_number:int, graphql_url:string} $legacy settings.github
     */
    public function __construct(
        private readonly GitHubAccountRepository      $accounts,
        private readonly ProjectRepository            $projects,
        private readonly AuditLogRepository           $audit,
        private readonly CredentialVault              $vault,
        private readonly GitHubClientFactoryInterface $clients,
        private readonly AccountConnectionTester      $tester,
        private readonly array                        $legacy,
    ) {
    }

    /**
     * @return array{status:string, account_id:?int, project_id:?int, account_created:bool, project_linked:bool,
     *               messages:string[], missing:string[]}
     */
    public function import(?int $actorId = null): array
    {
        $result = [
            'status' => 'imported', 'account_id' => null, 'project_id' => null,
            'account_created' => false, 'project_linked' => false, 'messages' => [], 'missing' => [],
        ];

        $pat    = trim((string) ($this->legacy['pat'] ?? ''));
        $org    = trim((string) ($this->legacy['org'] ?? ''));
        $number = (int) ($this->legacy['project_number'] ?? 0);
        $url    = trim((string) ($this->legacy['graphql_url'] ?? '')) ?: GraphqlEndpointValidator::DEFAULT_ENDPOINT;

        foreach (['REPO_PAT' => $pat === '', 'REPO_ORG' => $org === '', 'REPO_PROJECT_NUMBER' => $number < 1] as $name => $missing) {
            if ($missing) {
                $result['missing'][] = $name;
            }
        }
        if ($result['missing'] !== []) {
            $result['status']     = 'not_configured';
            $result['messages'][] = 'Missing legacy settings: ' . implode(', ', $result['missing']) . '.';

            return $result;
        }

        if (!$this->vault->isConfigured()) {
            $result['status']     = 'app_key_missing';
            $result['messages'][] = 'APP_KEY is not configured; generate one with cron/generate-app-key.php.';

            return $result;
        }

        $account = $this->accounts->findByOwner($org, $url);

        if ($account === null) {
            $accountId = $this->createAccount($org, $pat, $url, $actorId);
            $result['account_created'] = true;
            $result['messages'][]      = 'Account created from the .env settings.';
        } else {
            $accountId = (int) $account['id'];
            $result['messages'][] = 'An account for this owner already exists; its stored token was left unchanged.';
        }
        $result['account_id'] = $accountId;

        $this->linkProject($result, $accountId, $org, $number, $actorId);

        if (!$result['account_created'] && !$result['project_linked']) {
            $result['status'] = 'already_imported';
        } else {
            $this->audit->append($actorId, 'account.imported_from_env', 'github_account', $accountId, [
                'owner' => $org, 'account_created' => $result['account_created'], 'project_linked' => $result['project_linked'],
            ]);
        }

        return $result;
    }

    private function createAccount(string $org, string $pat, string $url, ?int $actorId): int
    {
        try {
            $type = $this->clients->forToken($pat, $url)->detectOwnerType($org) === 'User' ? 'user' : 'organization';
        } catch (\Throwable) {
            $type = 'organization';
        }

        $id  = $this->accounts->create([
            'label' => 'Imported from .env (' . $org . ')', 'owner_login' => $org, 'owner_type' => $type,
            'graphql_url' => $url, 'created_by' => $actorId,
        ]);
        $enc = $this->vault->encrypt($pat);
        $this->accounts->saveToken($id, $enc['ciphertext'], $enc['nonce'], CredentialVault::hint($pat));

        $row = $this->accounts->findWithSecret($id);
        try {
            $client = $this->clients->forAccount((array) $row);
            $test   = $this->tester->test($client, $org);
            $this->accounts->updateStatus($id, $test['status'], $test['message'], $test['verified_at'], $test['expires_at']);
        } catch (\Throwable) {
            $this->accounts->updateStatus($id, 'unreachable', 'Connection could not be tested during import.');
        }

        return $id;
    }

    /**
     * @param array<string,mixed> $result
     */
    private function linkProject(array &$result, int $accountId, string $org, int $number, ?int $actorId): void
    {
        $existing = $this->projects->findByOwnerAndNumber($org, $number);

        if ($existing !== null) {
            $result['project_id'] = (int) $existing['id'];

            if ($existing['account_id'] === null) {
                $this->projects->linkAccount((int) $existing['id'], $accountId);
                $result['project_linked'] = true;
                $result['messages'][]     = 'Existing project linked to the account; its data and history are unchanged.';
            }

            return;
        }

        // No local row yet (fresh install): look the project up on GitHub and track it.
        try {
            $account = $this->accounts->findWithSecret($accountId);
            $client  = $this->clients->forAccount((array) $account);
            foreach ($client->listOwnerProjects($org) as $candidate) {
                if ($candidate['number'] === $number) {
                    $id = $this->projects->track([
                        'account_id' => $accountId, 'github_project_id' => $candidate['id'], 'github_owner' => $org,
                        'project_number' => $number, 'name' => $candidate['title'],
                    ]);
                    $result['project_id']     = $id;
                    $result['project_linked'] = true;
                    $result['messages'][]     = 'Project "' . $candidate['title'] . '" is now tracked.';

                    return;
                }
            }
            $result['messages'][] = 'Project #' . $number . ' was not found for this owner; track it manually in the admin area.';
        } catch (\Throwable) {
            $result['messages'][] = 'The project could not be looked up on GitHub; track it manually in the admin area.';
        }
    }
}
