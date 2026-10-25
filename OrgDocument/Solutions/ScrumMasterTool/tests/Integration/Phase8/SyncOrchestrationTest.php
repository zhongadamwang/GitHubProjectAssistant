<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;
use App\Services\CredentialVault;
use App\Services\GitHubClientFactory;
use Tests\Integration\Phase8\Support\FakeGitHubClient;

/**
 * T051 — per-project sync orchestration: isolation, rate limits, mapping, snapshots, legacy fallback.
 */
final class SyncOrchestrationTest extends Phase8TestCase
{
    private ProjectRepository $projects;
    private GitHubAccountRepository $accounts;

    /** @var array<string,mixed> */
    private array $fixtureA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projects = new ProjectRepository(static::$pdo);
        $this->accounts = new GitHubAccountRepository(static::$pdo);

        $this->fixtureA = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/github-project-response.json'), true, 512, JSON_THROW_ON_ERROR);
        static::$pdo->exec('DELETE FROM `issues`');
        static::$pdo->exec('DELETE FROM `sync_history`');
    }

    /**
     * @return array<string,mixed> a copy of the fixture describing a different GitHub project
     */
    private function otherFixture(string $nodeId): array
    {
        $copy = $this->fixtureA;
        $copy['project_fields_response']['data']['organization']['projectV2']['id'] = $nodeId;

        return $copy;
    }

    /**
     * Account + tracked project backed by its own fake GitHub client.
     *
     * @param array<string,mixed> $fixture
     * @return array{account:int, project:int, client:FakeGitHubClient}
     */
    private function projectWithClient(string $owner, string $nodeId, int $number, array $fixture, ?string $token = null): array
    {
        $accountId = $this->seedAccount($owner, $token ?? 'ghp_' . strtoupper(str_pad($owner, 36, 'X')));
        $projectId = $this->projects->track([
            'account_id' => $accountId, 'github_project_id' => $nodeId, 'github_owner' => $owner,
            'project_number' => $number, 'name' => $owner . ' board',
        ]);

        $client = new FakeGitHubClient();
        $client->fixture = $fixture;
        $this->githubFactory->clientsByOwner[$owner] = $client;

        return ['account' => $accountId, 'project' => $projectId, 'client' => $client];
    }

    /** @return array<string,mixed> */
    private function projectRow(int $id): array
    {
        return (array) $this->projects->findById($id);
    }

    private function issueCount(int $projectId): int
    {
        $stmt = static::$pdo->prepare('SELECT COUNT(*) FROM `issues` WHERE `project_id` = ?');
        $stmt->execute([$projectId]);

        return (int) $stmt->fetchColumn();
    }

    // ---- multi-project run -----------------------------------------------

    public function test_run_all_syncs_each_tracked_project_with_its_own_account(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $b = $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'));

        $outcomes = $this->buildOrchestrator()->runAll();

        $this->assertCount(2, $outcomes);
        foreach ($outcomes as $outcome) {
            $this->assertSame('ok', $outcome->status, $outcome->message);
        }
        $this->assertSame(5, $this->issueCount($a['project']));
        $this->assertSame(5, $this->issueCount($b['project']));
        $this->assertSame('ok', $this->projectRow($a['project'])['last_sync_status']);
        $this->assertNotNull($this->projectRow($b['project'])['last_sync_at']);

        $historyProjects = static::$pdo->query('SELECT `project_id` FROM `sync_history` ORDER BY `id`')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertEqualsCanonicalizing([$a['project'], $b['project']], array_map('intval', $historyProjects));
    }

    public function test_each_account_token_is_used_for_its_own_project(): void
    {
        $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA, 'ghp_ALPHATOKENALPHATOKENALPHATOKENALPHAT');
        $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'), 'ghp_BETATOKENBETATOKENBETATOKENBETATOKE');

        $this->buildOrchestrator()->runAll();

        $this->assertSame(['ghp_ALPHATOKENALPHATOKENALPHATOKENALPHAT', 'ghp_BETATOKENBETATOKENBETATOKENBETATOKE'], $this->githubFactory->tokensSeen);
    }

    public function test_archived_projects_are_not_synced(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $this->projects->setTracked($a['project'], false);

        $this->assertSame([], $this->buildOrchestrator()->runAll());
        $this->assertSame(0, $this->issueCount($a['project']));
    }

    // ---- failure isolation ------------------------------------------------

    public function test_one_failing_project_does_not_stop_the_others(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $b = $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'));
        $a['client']->failWith = new GitHubApiException(401, [], 'GitHub API returned HTTP 401');

        $outcomes = $this->buildOrchestrator()->runAll();

        $this->assertSame('needs_attention', $outcomes[0]->status);
        $this->assertSame('ok', $outcomes[1]->status);
        $this->assertSame(0, $this->issueCount($a['project']));
        $this->assertSame(5, $this->issueCount($b['project']));
        $this->assertSame('needs_attention', $this->projectRow($a['project'])['last_sync_status']);
        $this->assertSame('invalid', static::$pdo->query("SELECT `status` FROM `github_accounts` WHERE `id` = {$a['account']}")->fetchColumn());
    }

    public function test_failed_run_is_recorded_in_history_with_the_project_id(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $a['client']->failWith = new GitHubApiException(503, [], 'GitHub API returned HTTP 503');

        $outcome = $this->buildOrchestrator()->runAll()[0];

        $this->assertSame('failed', $outcome->status);
        $row = static::$pdo->query('SELECT `project_id`, `status`, `error_message` FROM `sync_history`')->fetch();
        $this->assertSame($a['project'], (int) $row['project_id']);
        $this->assertSame('failed', $row['status']);
        $this->assertSame('failed', $this->projectRow($a['project'])['last_sync_status']);
    }

    public function test_a_successful_sync_clears_a_stale_account_error(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $this->accounts->updateStatus($a['account'], 'invalid', 'Bad credentials');

        $this->buildOrchestrator()->runAll();

        $this->assertSame('ok', static::$pdo->query("SELECT `status` FROM `github_accounts` WHERE `id` = {$a['account']}")->fetchColumn());
    }

    public function test_undecryptable_token_needs_attention_and_is_recorded(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        static::$pdo->prepare('UPDATE `github_accounts` SET `token_ciphertext` = ? WHERE `id` = ?')->execute(['s:' . base64_encode(str_repeat('x', 40)), $a['account']]);

        $outcome = $this->buildOrchestrator()->runAll()[0];

        $this->assertSame('needs_attention', $outcome->status);
        $this->assertSame('needs_reentry', static::$pdo->query("SELECT `status` FROM `github_accounts` WHERE `id` = {$a['account']}")->fetchColumn());
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `sync_history`')->fetchColumn());
    }

    public function test_missing_app_key_needs_attention_instead_of_crashing(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $noKey = new GitHubClientFactory(new CredentialVault(''));

        $outcome = $this->buildOrchestrator([], $noKey)->runAll()[0];

        $this->assertSame('needs_attention', $outcome->status);
        $this->assertStringContainsString('APP_KEY', $outcome->message);
        $this->assertSame('needs_attention', $this->projectRow($a['project'])['last_sync_status']);
    }

    public function test_project_without_account_needs_attention_when_no_legacy_credentials(): void
    {
        static::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name) VALUES ('PVT_L', 'acme', '', 3, 'Legacy')");

        $outcome = $this->buildOrchestrator()->runAll()[0];

        $this->assertSame('needs_attention', $outcome->status);
        $this->assertStringContainsString('not linked', $outcome->message);
    }

    // ---- rate limits ------------------------------------------------------

    public function test_low_quota_skips_remaining_projects_of_the_same_account(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $a['client']->meta['rate_remaining'] = 50; // below the floor after the first project
        $second = $this->projects->track([
            'account_id' => $a['account'], 'github_project_id' => 'PVT_second', 'github_owner' => 'alpha',
            'project_number' => 2, 'name' => 'Second',
        ]);

        $outcomes = $this->buildOrchestrator([], null, 200)->runAll();

        $this->assertSame('ok', $outcomes[0]->status);
        $this->assertSame('rate_limited', $outcomes[1]->status);
        $this->assertSame('rate_limited', $this->projectRow($second)['last_sync_status']);
        $skipped = static::$pdo->prepare("SELECT `status`, `error_message` FROM `sync_history` WHERE `project_id` = ?");
        $skipped->execute([$second]);
        $row = $skipped->fetch();
        $this->assertSame('failed', $row['status']);
        $this->assertStringContainsString('rate limit', $row['error_message']);
    }

    public function test_quota_of_one_account_does_not_block_another_account(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $a['client']->meta['rate_remaining'] = 10;
        $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'));

        $outcomes = $this->buildOrchestrator()->runAll();

        $this->assertSame(['ok', 'ok'], array_map(static fn($o) => $o->status, $outcomes));
    }

    public function test_rate_limit_exception_is_reported_as_rate_limited(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $a['client']->failWith = new RateLimitException(0, time() + 3600);

        $outcome = $this->buildOrchestrator()->runAll()[0];

        $this->assertSame('rate_limited', $outcome->status);
    }

    // ---- mapping and snapshots -------------------------------------------

    public function test_per_project_field_mapping_is_used(): void
    {
        $fixture = $this->fixtureA;
        $fixture['project_items_nodes'][0]['fieldValues']['nodes'] = [
            ['__typename' => 'ProjectV2ItemFieldNumberValue', 'number' => 7.0, 'field' => ['id' => 'F1', 'name' => 'Est']],
            ['__typename' => 'ProjectV2ItemFieldNumberValue', 'number' => 3.0, 'field' => ['id' => 'F2', 'name' => 'Rem']],
            ['__typename' => 'ProjectV2ItemFieldIterationValue', 'title' => 'Sprint 9', 'field' => ['id' => 'F3', 'name' => 'Sprint']],
        ];
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $fixture);
        $this->projects->setFieldMapping($a['project'], ['estimated' => 'Est', 'remaining' => 'Rem', 'iteration' => 'Sprint']);

        $this->buildOrchestrator()->runAll();

        $row = static::$pdo->query("SELECT `estimated_time`, `remaining_time`, `iteration` FROM `issues` WHERE `github_issue_id` = 'I_kgDOA1AAAAA'")->fetch();
        $this->assertEqualsWithDelta(7.0, (float) $row['estimated_time'], 0.001);
        $this->assertEqualsWithDelta(3.0, (float) $row['remaining_time'], 0.001);
        $this->assertSame('Sprint 9', $row['iteration']);
    }

    public function test_default_mapping_does_not_read_override_names_of_other_projects(): void
    {
        $fixture = $this->fixtureA;
        $fixture['project_items_nodes'][0]['fieldValues']['nodes'] = [
            ['__typename' => 'ProjectV2ItemFieldNumberValue', 'number' => 7.0, 'field' => ['id' => 'F1', 'name' => 'Est']],
        ];
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $fixture); // no override

        $this->buildOrchestrator()->runAll();

        $estimated = static::$pdo->query("SELECT `estimated_time` FROM `issues` WHERE `github_issue_id` = 'I_kgDOA1AAAAA'")->fetchColumn();
        $this->assertEqualsWithDelta(0.0, (float) $estimated, 0.001);
        $this->assertGreaterThan(0, $a['project']);
    }

    public function test_snapshots_are_written_per_project(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $b = $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'));

        $outcomes = $this->buildOrchestrator()->runAll();

        $filesA = glob($this->snapshotRoot . '/' . $a['project'] . '/*.json') ?: [];
        $filesB = glob($this->snapshotRoot . '/' . $b['project'] . '/*.json') ?: [];
        $this->assertCount(1, $filesA);
        $this->assertCount(1, $filesB);
        $this->assertNotSame($outcomes[0]->result->snapshotFile, $outcomes[1]->result->snapshotFile);
    }

    public function test_snapshot_retention_prunes_the_oldest_files_per_project(): void
    {
        $a   = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $dir = $this->snapshotRoot . '/' . $a['project'];
        mkdir($dir, 0755, true);
        foreach (['2020-01-01_00-00-01', '2020-01-01_00-00-02', '2020-01-01_00-00-03'] as $name) {
            file_put_contents("{$dir}/{$name}.json", '{}');
        }

        $this->buildOrchestrator([], null, 200, 2)->runAll();

        $remaining = array_map('basename', glob($dir . '/*.json') ?: []);
        sort($remaining);
        $this->assertCount(2, $remaining);
        $this->assertSame('2020-01-01_00-00-03.json', $remaining[0], 'older snapshots are removed first');
    }

    // ---- legacy fallback --------------------------------------------------

    public function test_unlinked_project_uses_legacy_env_credentials_when_configured(): void
    {
        static::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name) VALUES ('PVT_kwTest01', 'acme', '', 3, 'Legacy')");
        $this->github->fixture = $this->fixtureA;

        $outcome = $this->buildOrchestrator(['pat' => 'legacy-env-token', 'org' => 'acme', 'project_number' => 3])->runAll()[0];

        $this->assertSame('ok', $outcome->status, $outcome->message);
        $this->assertContains('legacy-env-token', $this->githubFactory->tokensSeen);
        $this->assertSame(5, (int) static::$pdo->query('SELECT COUNT(*) FROM `issues`')->fetchColumn());
    }

    public function test_legacy_credentials_log_one_deprecation_notice_without_the_token(): void
    {
        $log = sys_get_temp_dir() . '/scrum_p8_dep_' . uniqid('', true) . '.log';
        ini_set('error_log', $log);
        static::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name) VALUES ('PVT_a', 'acme', '', 3, 'A'), ('PVT_b', 'acme', '', 4, 'B')");
        $this->github->fixture = $this->fixtureA;

        $this->buildOrchestrator(['pat' => 'legacy-env-token', 'org' => 'acme', 'project_number' => 3])->runAll();

        $text = (string) file_get_contents($log);
        @unlink($log);
        $this->assertSame(1, substr_count($text, 'DEPRECATED'));
        $this->assertStringNotContainsString('legacy-env-token', $text);
    }

    public function test_fresh_legacy_install_bootstraps_the_project_from_env(): void
    {
        $this->github->fixture = $this->fixtureA;

        $outcomes = $this->buildOrchestrator(['pat' => 'legacy-env-token', 'org' => 'acme', 'project_number' => 1])->runAll();

        $this->assertCount(1, $outcomes);
        $this->assertSame('ok', $outcomes[0]->status, $outcomes[0]->message);
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects` WHERE `is_tracked` = 1')->fetchColumn());
    }

    // ---- single project / HTTP --------------------------------------------

    public function test_sync_project_returns_null_for_unknown_ids(): void
    {
        $this->assertNull($this->buildOrchestrator()->syncProject(999999));
    }

    public function test_sync_endpoint_syncs_one_project(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $b = $this->projectWithClient('beta', 'PVT_other', 2, $this->otherFixture('PVT_other'));
        $this->useOrchestrator($this->buildOrchestrator());
        $this->loginAsAdmin();

        $response = $this->request('POST', "/api/projects/{$b['project']}/sync");

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = $this->json($response);
        $this->assertSame('ok', $body['status']);
        $this->assertSame(5, $body['issues_added']);
        $this->assertSame(0, $this->issueCount($a['project']), 'Only the requested project is synced');
        $this->assertSame(5, $this->issueCount($b['project']));
    }

    /** @return array<string,array{callable,int}> */
    public static function endpointFailureProvider(): array
    {
        return [
            'github down'  => [static fn() => new GitHubApiException(503, [], 'GitHub API returned HTTP 503'), 502],
            'bad token'    => [static fn() => new GitHubApiException(401, [], 'GitHub API returned HTTP 401'), 409],
            'rate limited' => [static fn() => new RateLimitException(0, time() + 60), 429],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpointFailureProvider')]
    public function test_sync_endpoint_maps_outcomes_to_http_status(callable $failure, int $expected): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $a['client']->failWith = $failure();
        $this->useOrchestrator($this->buildOrchestrator());
        $this->loginAsAdmin();

        $response = $this->request('POST', "/api/projects/{$a['project']}/sync");

        $this->assertSame($expected, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->json($response));
    }

    public function test_sync_endpoint_rejects_unknown_and_archived_projects_and_non_admins(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $this->projects->setTracked($a['project'], false);
        $this->useOrchestrator($this->buildOrchestrator());

        $this->assertSame(401, $this->request('POST', "/api/projects/{$a['project']}/sync")->getStatusCode());

        $this->authenticateSession($this->seedMember('member@test.local', 'Member1234!'));
        $this->assertSame(403, $this->request('POST', "/api/projects/{$a['project']}/sync")->getStatusCode());

        $this->loginAsAdmin();
        $this->assertSame(404, $this->request('POST', '/api/projects/999999/sync')->getStatusCode());
        $this->assertSame(409, $this->request('POST', "/api/projects/{$a['project']}/sync")->getStatusCode());
    }

    public function test_legacy_trigger_alias_syncs_the_first_tracked_project(): void
    {
        $a = $this->projectWithClient('alpha', 'PVT_kwTest01', 1, $this->fixtureA);
        $this->useOrchestrator($this->buildOrchestrator());
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/sync/trigger');

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(5, $this->issueCount($a['project']));
    }

    public function test_legacy_trigger_without_any_project_returns_409(): void
    {
        $this->useOrchestrator($this->buildOrchestrator());
        $this->loginAsAdmin();

        $this->assertSame(409, $this->request('POST', '/api/sync/trigger')->getStatusCode());
    }

    public function test_sync_history_works_without_any_github_credentials(): void
    {
        // The default container (no REPO_PAT, no account) must still serve the history endpoint
        $this->loginAsAdmin();

        $this->assertSame(200, $this->request('GET', '/api/sync/history')->getStatusCode());
    }
}
