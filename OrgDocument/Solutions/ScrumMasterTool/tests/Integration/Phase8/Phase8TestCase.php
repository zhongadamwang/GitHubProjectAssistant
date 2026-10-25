<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Controllers\GitHubAccountController;
use App\Controllers\ProjectAdminController;
use App\Controllers\SyncController;
use App\Repositories\AuditLogRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;
use App\Services\AccountConnectionTester;
use App\Services\CredentialVault;
use App\Services\GitHubClientFactoryInterface;
use App\Services\BurndownService;
use App\Services\EnvAccountImporter;
use App\Services\GraphqlEndpointValidator;
use App\Services\SyncOrchestrator;
use App\Services\SyncServiceFactory;
use Tests\Integration\IntegrationTestCase;
use Tests\Integration\Phase8\Support\FakeGitHubClient;
use Tests\Integration\Phase8\Support\FakeGitHubClientFactory;

/**
 * Base class for Phase 8 tests: starts every test with no GitHub accounts,
 * projects or audit entries (the admin user is seeded by the parent class).
 */
abstract class Phase8TestCase extends IntegrationTestCase
{
    protected FakeGitHubClient $github;

    protected FakeGitHubClientFactory $githubFactory;

    /** Temporary snapshot root used by orchestrators built through buildOrchestrator(). */
    protected string $snapshotRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanPhase8Tables();
        $this->snapshotRoot = sys_get_temp_dir() . '/scrum_p8_' . uniqid('', true);
        $this->installFakeGitHub();
    }

    /**
     * Orchestrator wired to the fake GitHub (or the given client factory) and the temp snapshot root.
     *
     * @param array{pat?:string, org?:string, project_number?:int, graphql_url?:string} $legacy
     */
    protected function buildOrchestrator(array $legacy = [], ?GitHubClientFactoryInterface $clients = null, int $rateFloor = SyncOrchestrator::RATE_FLOOR, int $retention = 0): SyncOrchestrator
    {
        $c       = static::$app->getContainer();
        $clients ??= $this->githubFactory;
        $pdo     = static::$pdo;
        $issues  = new IssueRepository($pdo);

        $factory = new SyncServiceFactory(
            $c->get(ProjectRepository::class),
            $issues,
            new SyncHistoryRepository($pdo),
            new BurndownService($c->get(\App\Repositories\BurndownRepository::class), $issues),
            new TimeLogRepository($pdo),
            $clients,
            $c->get('settings')['github']['fields'],
            $this->snapshotRoot,
            $retention,
        );

        return new SyncOrchestrator(
            $c->get(ProjectRepository::class),
            $c->get(GitHubAccountRepository::class),
            new SyncHistoryRepository($pdo),
            $factory,
            $c->get(AccountConnectionTester::class),
            $legacy + ['pat' => '', 'org' => '', 'project_number' => 0, 'graphql_url' => 'https://api.github.com/graphql'],
            $rateFloor,
        );
    }

    /**
     * Use the given orchestrator for the HTTP sync endpoints.
     */
    protected function useOrchestrator(SyncOrchestrator $orchestrator): void
    {
        $c = static::$app->getContainer();
        $c->set(SyncOrchestrator::class, $orchestrator);
        $c->set(SyncController::class, new SyncController(
            $c->get(SyncHistoryRepository::class),
            $orchestrator,
            $c->get(ProjectRepository::class),
        ));
    }

    /**
     * Replace the GitHub client factory (and the controllers that use it) with a fake,
     * so API tests never touch the network.
     */
    protected function installFakeGitHub(?CredentialVault $vault = null): void
    {
        $c     = static::$app->getContainer();
        $vault ??= $c->get(CredentialVault::class);

        $this->github        = new FakeGitHubClient();
        $this->githubFactory = new FakeGitHubClientFactory($this->github, $vault);

        $c->set(GitHubClientFactoryInterface::class, $this->githubFactory);
        $this->useImporter([], $vault);
        $c->set(ProjectAdminController::class, new ProjectAdminController(
            $c->get(ProjectRepository::class),
            $c->get(GitHubAccountRepository::class),
            $c->get(AuditLogRepository::class),
            $this->githubFactory,
            $c->get(AccountConnectionTester::class),
            $c->get('settings')['github']['fields'],
        ));
        $this->useOrchestrator($this->buildOrchestrator());
    }

    /**
     * Wire the account controller (and the .env importer) with the given legacy settings.
     *
     * @param array{pat?:string, org?:string, project_number?:int, graphql_url?:string} $legacy
     */
    protected function useImporter(array $legacy, ?CredentialVault $vault = null): void
    {
        $c     = static::$app->getContainer();
        $vault ??= $c->get(CredentialVault::class);

        $importer = new EnvAccountImporter(
            $c->get(GitHubAccountRepository::class),
            $c->get(ProjectRepository::class),
            $c->get(AuditLogRepository::class),
            $vault,
            $this->githubFactory,
            $c->get(AccountConnectionTester::class),
            $legacy + ['pat' => '', 'org' => '', 'project_number' => 0, 'graphql_url' => 'https://api.github.com/graphql'],
        );

        $c->set(EnvAccountImporter::class, $importer);
        $c->set(GitHubAccountController::class, new GitHubAccountController(
            $c->get(GitHubAccountRepository::class),
            $c->get(ProjectRepository::class),
            $c->get(AuditLogRepository::class),
            $vault,
            $this->githubFactory,
            $c->get(AccountConnectionTester::class),
            $c->get(GraphqlEndpointValidator::class),
            $importer,
        ));
    }

    /**
     * Insert a stored account with an encrypted token directly (no GitHub involved).
     *
     * @return int account id
     */
    protected function seedAccount(string $owner = 'acme', string $token = 'ghp_SEEDTOKENSEEDTOKENSEEDTOKENSEEDTOKEN'): int
    {
        $accounts = new GitHubAccountRepository(static::$pdo);
        $vault    = static::$app->getContainer()->get(CredentialVault::class);

        $id  = $accounts->create(['label' => ucfirst($owner), 'owner_login' => $owner]);
        $enc = $vault->encrypt($token);
        $accounts->saveToken($id, $enc['ciphertext'], $enc['nonce'], CredentialVault::hint($token));
        $accounts->updateStatus($id, 'ok', 'Seeded', gmdate('Y-m-d H:i:s'));

        return $id;
    }

    protected function tearDown(): void
    {
        $this->cleanPhase8Tables();
        $this->removeDirectory($this->snapshotRoot);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function cleanPhase8Tables(): void
    {
        static::$pdo->exec('DELETE FROM `admin_audit_log`');
        static::$pdo->exec('DELETE FROM `projects`');
        static::$pdo->exec('DELETE FROM `github_accounts`');
    }
}
