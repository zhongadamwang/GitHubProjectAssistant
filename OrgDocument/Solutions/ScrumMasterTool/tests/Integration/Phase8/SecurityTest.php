<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Exceptions\GitHubApiException;
use App\Services\GitHubClientFactory;
use App\Services\CredentialVault;
use App\Services\SecretRedactor;

/**
 * T054 — credential security: no token or ciphertext leaves the server, state-changing admin
 * requests are JSON-only, the GitHub endpoint cannot be pointed at internal addresses, and the
 * API never discloses server paths.
 */
final class SecurityTest extends Phase8TestCase
{
    private const TOKEN_ONE   = 'ghp_ONEONEONEONEONEONEONEONEONEONE1111';
    private const TOKEN_TWO   = 'ghp_TWOTWOTWOTWOTWOTWOTWOTWOTWOTWO2222';
    private const TOKEN_LEGACY = 'ghp_LEGACYLEGACYLEGACYLEGACYLEGACY3333';

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = sys_get_temp_dir() . '/scrum_p8_log_' . uniqid('', true) . '.log';
        ini_set('error_log', $this->logFile);
        $this->github->projects = [['id' => 'PVT_kwSec', 'number' => 5, 'title' => 'Secure Board', 'closed' => false, 'url' => 'https://github.com/orgs/acme/projects/5', 'shortDescription' => '']];
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);
        parent::tearDown();
    }

    /**
     * @return array<string,string> label → response body
     */
    private function exerciseEveryEndpoint(): array
    {
        $bodies = [];
        $call   = function (string $label, string $method, string $path, mixed $payload = null) use (&$bodies): array {
            $response = $this->request($method, $path, $payload);
            $bodies[$label] = (string) $response->getBody();

            return [$response->getStatusCode(), json_decode($bodies[$label], true) ?? []];
        };

        $this->loginAsAdmin();

        [, $created] = $call('create', 'POST', '/api/admin/github-accounts', ['label' => 'Acme', 'owner_login' => 'acme', 'token' => self::TOKEN_ONE]);
        $id = (int) $created['account']['id'];

        $call('list', 'GET', '/api/admin/github-accounts');
        $call('update-label', 'PUT', "/api/admin/github-accounts/{$id}", ['label' => 'Acme 2']);
        $call('replace-token', 'PUT', "/api/admin/github-accounts/{$id}", ['token' => self::TOKEN_TWO]);
        $call('test', 'POST', "/api/admin/github-accounts/{$id}/test");
        $call('discover', 'GET', "/api/admin/github-accounts/{$id}/projects");
        [, $tracked] = $call('track', 'POST', '/api/admin/projects', ['account_id' => $id, 'number' => 5]);
        $projectId = (int) $tracked['project']['id'];
        $call('projects', 'GET', '/api/projects?include_archived=1');
        $call('map', 'PUT', "/api/admin/projects/{$projectId}", ['field_mapping' => ['estimated' => 'Est']]);
        $call('validate', 'POST', "/api/admin/projects/{$projectId}/validate-fields");

        // A GitHub failure whose message echoes the token must not reach any response, log or table
        $this->github->failWith = new GitHubApiException(500, [], 'upstream rejected ' . self::TOKEN_TWO . ' with Bearer ' . self::TOKEN_TWO);
        $call('sync-failed', 'POST', "/api/projects/{$projectId}/sync");
        $call('test-failed', 'POST', "/api/admin/github-accounts/{$id}/test");
        $this->github->failWith = null;

        $call('history', 'GET', '/api/sync/history');
        $call('audit', 'GET', '/api/admin/audit-log');
        $call('untrack', 'DELETE', "/api/admin/projects/{$projectId}");
        $call('delete-data', 'DELETE', "/api/admin/projects/{$projectId}/data", ['confirm' => 'Secure Board']);
        $call('delete-account', 'DELETE', "/api/admin/github-accounts/{$id}");

        // Legacy import path
        $this->useImporter(['pat' => self::TOKEN_LEGACY, 'org' => 'legacyorg', 'project_number' => 5]);
        $call('import-env', 'POST', '/api/admin/github-accounts/import-env');
        $call('list-after-import', 'GET', '/api/admin/github-accounts');

        return $bodies;
    }

    public function test_no_response_log_or_table_exposes_a_token_or_ciphertext(): void
    {
        $bodies = $this->exerciseEveryEndpoint();

        $ciphertexts = static::$pdo->query('SELECT `token_ciphertext` FROM `github_accounts` WHERE `token_ciphertext` IS NOT NULL')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertNotEmpty($ciphertexts, 'The legacy import should have stored an encrypted token');

        $haystacks = $bodies;
        $haystacks['error_log'] = is_file($this->logFile) ? (string) file_get_contents($this->logFile) : '';
        foreach (['admin_audit_log', 'sync_history', 'projects'] as $table) {
            $haystacks["table:{$table}"] = json_encode(static::$pdo->query("SELECT * FROM `{$table}`")->fetchAll());
        }
        $haystacks['table:github_accounts(public)'] = json_encode(static::$pdo->query(
            'SELECT `id`, `label`, `owner_login`, `token_hint`, `status`, `status_message` FROM `github_accounts`'
        )->fetchAll());

        $secrets = [self::TOKEN_ONE, self::TOKEN_TWO, self::TOKEN_LEGACY, ...$ciphertexts];

        foreach ($haystacks as $where => $text) {
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, (string) $text, "A secret leaked into: {$where}");
            }
        }
        foreach ($bodies as $where => $text) {
            $this->assertStringNotContainsString('token_ciphertext', $text, "Ciphertext column leaked into: {$where}");
            $this->assertStringNotContainsString('token_nonce', $text, "Nonce column leaked into: {$where}");
        }
    }

    public function test_the_stored_token_is_ciphertext_and_decrypts_only_with_the_key(): void
    {
        $this->loginAsAdmin();
        $this->request('POST', '/api/admin/github-accounts', ['label' => 'Acme', 'owner_login' => 'acme', 'token' => self::TOKEN_ONE]);

        $row = static::$pdo->query('SELECT * FROM `github_accounts`')->fetch();

        $this->assertNotSame(self::TOKEN_ONE, $row['token_ciphertext']);
        $this->assertStringNotContainsString(self::TOKEN_ONE, json_encode($row));
        $vault = static::$app->getContainer()->get(CredentialVault::class);
        $this->assertSame(self::TOKEN_ONE, $vault->decrypt($row['token_ciphertext'], $row['token_nonce']));
    }

    public function test_redactor_removes_github_token_shapes(): void
    {
        $text = 'a ghp_' . str_repeat('A', 30) . ' b github_pat_' . str_repeat('b', 30) . ' c Bearer abcdefghijklmnopqrstuvwxyz';

        $clean = SecretRedactor::redact($text);

        $this->assertStringNotContainsString('ghp_', $clean);
        $this->assertStringNotContainsString('github_pat_', $clean);
        $this->assertStringNotContainsString('Bearer abc', $clean);
        $this->assertSame('ordinary message', SecretRedactor::redact('ordinary message'));
    }

    // ---- CSRF posture -----------------------------------------------------

    public function test_state_changing_admin_requests_must_be_json(): void
    {
        $this->loginAsAdmin();

        // IntegrationTestCase::request() always sends JSON, so build the form request explicitly
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/api/admin/github-accounts')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream('label=x&owner_login=y&token=z'));

        $response = static::$app->handle($request);

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_a_body_announced_only_by_content_length_must_still_be_json(): void
    {
        $this->loginAsAdmin();
        // php://input reports no size on a real server; only the headers reveal the body
        $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('POST', '/api/admin/github-accounts')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Content-Length', '30');

        $this->assertSame(415, static::$app->handle($request)->getStatusCode());
    }

    public function test_bodyless_state_changing_requests_still_work(): void
    {
        $this->loginAsAdmin();
        $id = $this->seedAccount('acme', self::TOKEN_ONE);

        $this->assertNotSame(415, $this->request('POST', "/api/admin/github-accounts/{$id}/test")->getStatusCode());
    }

    public function test_session_cookie_defaults_to_samesite_strict(): void
    {
        $settings = static::$app->getContainer()->get('settings');

        $this->assertSame('Strict', $settings['session']['same_site']);
    }

    // ---- SSRF and path exposure ------------------------------------------

    /** @return array<string,array{string}> */
    public static function forbiddenEndpointProvider(): array
    {
        return [
            'http'      => ['http://api.github.com/graphql'],
            'loopback'  => ['https://127.0.0.1/graphql'],
            'metadata'  => ['https://169.254.169.254/latest'],
            'private'   => ['https://10.0.0.5/graphql'],
            'localhost' => ['https://localhost/graphql'],
            'creds'     => ['https://user:pw@api.github.com/graphql'],
            'port'      => ['https://api.github.com:8443/graphql'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenEndpointProvider')]
    public function test_the_client_factory_refuses_internal_endpoints_at_use_time(string $url): void
    {
        $factory = new GitHubClientFactory(static::$app->getContainer()->get(CredentialVault::class));

        $this->expectException(GitHubApiException::class);
        $factory->forToken(self::TOKEN_ONE, $url);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenEndpointProvider')]
    public function test_creating_an_account_with_an_internal_endpoint_is_rejected(string $url): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/github-accounts', ['label' => 'x', 'owner_login' => 'acme', 'token' => self::TOKEN_ONE, 'graphql_url' => $url]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('graphql_url', $this->json($response)['errors']);
    }

    public function test_the_default_endpoint_is_accepted_without_a_dns_lookup(): void
    {
        $factory = new GitHubClientFactory(static::$app->getContainer()->get(CredentialVault::class));

        $this->assertNotNull($factory->forToken(self::TOKEN_ONE, 'https://api.github.com/graphql'));
    }

    public function test_sync_responses_and_history_do_not_expose_server_paths(): void
    {
        $fixture = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/github-project-response.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->github->fixture = $fixture;
        $account = $this->seedAccount('acme', self::TOKEN_ONE);
        $projects = new \App\Repositories\ProjectRepository(static::$pdo);
        $projectId = $projects->track(['account_id' => $account, 'github_project_id' => 'PVT_kwTest01', 'github_owner' => 'acme', 'project_number' => 1, 'name' => 'Board']);
        $this->useOrchestrator($this->buildOrchestrator());
        $this->loginAsAdmin();

        $sync = $this->json($this->request('POST', "/api/projects/{$projectId}/sync"));
        $history = $this->json($this->request('GET', '/api/sync/history'));

        $this->assertNotEmpty($sync['snapshot_file']);
        $this->assertStringNotContainsString('/', $sync['snapshot_file']);
        $this->assertNotEmpty($history['data'][0]['snapshot_file']);
        $this->assertStringNotContainsString('/', $history['data'][0]['snapshot_file']);
        $this->assertStringNotContainsString($this->snapshotRoot, json_encode([$sync, $history]));
    }
}
