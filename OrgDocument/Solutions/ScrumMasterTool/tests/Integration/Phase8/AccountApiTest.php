<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\Repositories\AuditLogRepository;
use App\Services\CredentialVault;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * T048 — GitHub account administration API (admin-only, token write-only).
 */
final class AccountApiTest extends Phase8TestCase
{
    private const TOKEN  = 'ghp_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    private const TOKEN2 = 'ghp_BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

    /** @return array<string,mixed> */
    private function validBody(array $override = []): array
    {
        return $override + ['label' => 'Acme', 'owner_login' => 'acme', 'token' => self::TOKEN];
    }

    /** @return array<string,mixed> */
    private function createAccount(array $override = []): array
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/admin/github-accounts', $this->validBody($override));
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response)['account'];
    }

    // ---- access control ---------------------------------------------------

    /** @return array<string,array{string,string}> */
    public static function endpointProvider(): array
    {
        return [
            'list'     => ['GET', '/api/admin/github-accounts'],
            'create'   => ['POST', '/api/admin/github-accounts'],
            'update'   => ['PUT', '/api/admin/github-accounts/1'],
            'delete'   => ['DELETE', '/api/admin/github-accounts/1'],
            'test'     => ['POST', '/api/admin/github-accounts/1/test'],
            'discover' => ['GET', '/api/admin/github-accounts/1/projects'],
            'audit'    => ['GET', '/api/admin/audit-log'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function test_unauthenticated_requests_get_401(string $method, string $path): void
    {
        $this->assertSame(401, $this->request($method, $path)->getStatusCode());
    }

    #[DataProvider('endpointProvider')]
    public function test_members_get_403(string $method, string $path): void
    {
        $this->authenticateSession($this->seedMember('member@test.local', 'Member1234!'));

        $this->assertSame(403, $this->request($method, $path)->getStatusCode());
    }

    // ---- create -----------------------------------------------------------

    public function test_create_returns_account_without_any_token_material(): void
    {
        $account = $this->createAccount();

        $this->assertSame('Acme', $account['label']);
        $this->assertSame('acme', $account['owner_login']);
        $this->assertTrue($account['has_token']);
        $this->assertSame('AAAA', $account['token_hint']);
        $this->assertSame('ok', $account['status']);
        $this->assertArrayNotHasKey('token', $account);
        $this->assertArrayNotHasKey('token_ciphertext', $account);
        $this->assertArrayNotHasKey('token_nonce', $account);
    }

    public function test_token_is_stored_encrypted_and_never_in_plaintext(): void
    {
        $account = $this->createAccount();

        $row = static::$pdo->query('SELECT * FROM `github_accounts`')->fetch();
        foreach ($row as $value) {
            $this->assertStringNotContainsString(self::TOKEN, (string) $value);
        }

        $vault = static::$app->getContainer()->get(CredentialVault::class);
        $this->assertSame(self::TOKEN, $vault->decrypt($row['token_ciphertext'], $row['token_nonce']));
        $this->assertSame($account['id'], (int) $row['id']);
    }

    public function test_create_detects_user_owner_type(): void
    {
        $this->github->ownerType = 'User';

        $account = $this->createAccount(['owner_login' => 'octocat-user']);

        $this->assertSame('user', $account['owner_type']);
    }

    public function test_create_records_a_secret_free_audit_entry(): void
    {
        $this->createAccount();

        $entries = (new AuditLogRepository(static::$pdo))->latest(5);
        $this->assertSame('account.created', $entries[0]['action']);

        $raw = (string) static::$pdo->query('SELECT GROUP_CONCAT(`detail`) FROM `admin_audit_log`')->fetchColumn();
        $this->assertStringNotContainsString(self::TOKEN, $raw);
    }

    public function test_status_reflects_a_failed_connection_test_but_the_account_is_still_saved(): void
    {
        $this->github->failWith = new GitHubApiException(401, [], 'GitHub API request failed with HTTP 401');

        $account = $this->createAccount();

        $this->assertSame('invalid', $account['status']);
    }

    /** @return array<string,array{array<string,mixed>,string}> */
    public static function invalidCreateProvider(): array
    {
        return [
            'missing label'      => [['label' => ''], 'label'],
            'bad owner'          => [['owner_login' => 'bad owner!'], 'owner_login'],
            'owner with slash'   => [['owner_login' => 'a/b'], 'owner_login'],
            'short token'        => [['token' => 'short'], 'token'],
            'token with spaces'  => [['token' => 'ghp_ has spaces in it xxxxxxxxxxxxxxxxx'], 'token'],
            'http endpoint'      => [['graphql_url' => 'http://ghe.example.com/graphql'], 'graphql_url'],
            'localhost endpoint' => [['graphql_url' => 'https://localhost/graphql'], 'graphql_url'],
            'private ip'         => [['graphql_url' => 'https://10.0.0.5/graphql'], 'graphql_url'],
            'link-local ip'      => [['graphql_url' => 'https://169.254.169.254/latest'], 'graphql_url'],
            'credentials in url' => [['graphql_url' => 'https://user:pw@ghe.example.com/graphql'], 'graphql_url'],
            'odd port'           => [['graphql_url' => 'https://ghe.example.com:8443/graphql'], 'graphql_url'],
        ];
    }

    /**
     * @param array<string,mixed> $override
     */
    #[DataProvider('invalidCreateProvider')]
    public function test_create_validates_input(array $override, string $field): void
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/admin/github-accounts', $this->validBody($override));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey($field, $this->json($response)['errors']);
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_duplicate_owner_and_endpoint_returns_409(): void
    {
        $this->createAccount();

        $response = $this->request('POST', '/api/admin/github-accounts', $this->validBody(['label' => 'Again']));

        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_create_without_app_key_returns_503(): void
    {
        $this->installFakeGitHub(new CredentialVault(''));
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/github-accounts', $this->validBody());

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringContainsString('APP_KEY', (string) $response->getBody());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    // ---- list / update ----------------------------------------------------

    public function test_list_never_exposes_tokens(): void
    {
        $this->createAccount();

        $body = (string) $this->request('GET', '/api/admin/github-accounts')->getBody();

        $this->assertStringContainsString('"token_hint":"AAAA"', $body);
        $this->assertStringNotContainsString(self::TOKEN, $body);
        $this->assertStringNotContainsString('ciphertext', $body);
    }

    public function test_update_changes_label_only_without_a_new_test(): void
    {
        $account = $this->createAccount();
        $this->github->calls = [];

        $response = $this->request('PUT', "/api/admin/github-accounts/{$account['id']}", ['label' => 'Acme Corp']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Acme Corp', $this->json($response)['account']['label']);
        $this->assertSame([], $this->github->calls, 'A label change must not call GitHub');
    }

    public function test_replacing_the_token_stores_the_new_one_and_retests(): void
    {
        $account = $this->createAccount();
        $this->githubFactory->tokensSeen = [];

        $response = $this->request('PUT', "/api/admin/github-accounts/{$account['id']}", ['token' => self::TOKEN2]);

        $this->assertSame(200, $response->getStatusCode());
        $updated = $this->json($response)['account'];
        $this->assertSame('BBBB', $updated['token_hint']);
        $this->assertSame('ok', $updated['status']);
        $this->assertContains(self::TOKEN2, $this->githubFactory->tokensSeen, 'The new token is the one used for the re-test');
        $this->assertStringNotContainsString(self::TOKEN2, (string) $response->getBody());

        $actions = array_column((new AuditLogRepository(static::$pdo))->latest(5), 'action');
        $this->assertContains('account.token_replaced', $actions);
    }

    public function test_update_validates_and_handles_unknown_accounts(): void
    {
        $account = $this->createAccount();

        $bad = $this->request('PUT', "/api/admin/github-accounts/{$account['id']}", ['token' => 'short']);
        $this->assertSame(422, $bad->getStatusCode());

        $missing = $this->request('PUT', '/api/admin/github-accounts/999999', ['label' => 'x']);
        $this->assertSame(404, $missing->getStatusCode());
    }

    // ---- delete -----------------------------------------------------------

    public function test_delete_removes_account_and_its_token(): void
    {
        $account = $this->createAccount();

        $response = $this->request('DELETE', "/api/admin/github-accounts/{$account['id']}");

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_delete_is_blocked_while_projects_reference_the_account(): void
    {
        $account = $this->createAccount();
        static::$pdo->prepare(
            "INSERT INTO `projects` (account_id, github_project_id, github_owner, github_repo, project_number, name)
             VALUES (?, 'PVT_1', 'acme', '', 1, 'Board')"
        )->execute([$account['id']]);

        $response = $this->request('DELETE', "/api/admin/github-accounts/{$account['id']}");

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_delete_unknown_account_returns_404(): void
    {
        $this->loginAsAdmin();

        $this->assertSame(404, $this->request('DELETE', '/api/admin/github-accounts/999999')->getStatusCode());
    }

    // ---- test connection --------------------------------------------------

    /** @return array<string,array{callable,string}> */
    public static function failureProvider(): array
    {
        return [
            'bad credentials'   => [static fn() => new GitHubApiException(401, [], 'GitHub API request failed with HTTP 401'), 'invalid'],
            'forbidden'         => [static fn() => new GitHubApiException(403, [], 'GitHub API request failed with HTTP 403'), 'insufficient_scope'],
            'graphql scope'     => [static fn() => GitHubApiException::fromGraphqlErrors(200, [['message' => 'Your token has not been granted the required scopes']]), 'insufficient_scope'],
            'network down'      => [static fn() => new GitHubApiException(0, [], 'cURL error 6'), 'unreachable'],
            'rate limited'      => [static fn() => new RateLimitException(0, time() + 60), 'unreachable'],
            'owner not found'   => [static fn() => GitHubApiException::fromGraphqlErrors(200, [['message' => 'Could not resolve to an Organization with the login of acme.']]), 'invalid'],
        ];
    }

    #[DataProvider('failureProvider')]
    public function test_connection_failures_map_to_account_status(callable $failure, string $expectedStatus): void
    {
        $account = $this->createAccount();
        $this->github->failWith = $failure();

        $response = $this->request('POST', "/api/admin/github-accounts/{$account['id']}/test");

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedStatus, $this->json($response)['account']['status']);
    }

    public function test_classic_token_without_project_scope_is_flagged(): void
    {
        $account = $this->createAccount();
        $this->github->meta['scopes'] = ['repo'];

        $body = $this->json($this->request('POST', "/api/admin/github-accounts/{$account['id']}/test"));

        $this->assertSame('insufficient_scope', $body['account']['status']);
        $this->assertStringContainsString('read:project', $body['result']['message']);
    }

    public function test_expired_token_is_flagged_and_expiry_recorded(): void
    {
        $account = $this->createAccount();
        $this->github->meta['token_expires_at'] = '2020-01-01 00:00:00';

        $body = $this->json($this->request('POST', "/api/admin/github-accounts/{$account['id']}/test"));

        $this->assertSame('expired', $body['account']['status']);
    }

    public function test_successful_test_records_expiry_and_project_count(): void
    {
        $account = $this->createAccount();
        $future = gmdate('Y-m-d H:i:s', time() + 86400 * 30);
        $this->github->meta['token_expires_at'] = $future;
        $this->github->projects = [['id' => 'PVT_1', 'number' => 1, 'title' => 'A', 'closed' => false, 'url' => null, 'shortDescription' => null]];

        $body = $this->json($this->request('POST', "/api/admin/github-accounts/{$account['id']}/test"));

        $this->assertSame('ok', $body['account']['status']);
        $this->assertSame($future, $body['account']['token_expires_at']);
        $this->assertStringContainsString('1 project', $body['result']['message']);
    }

    public function test_undecryptable_token_becomes_needs_reentry(): void
    {
        $account = $this->createAccount();
        static::$pdo->prepare('UPDATE `github_accounts` SET `token_ciphertext` = ? WHERE `id` = ?')->execute(['s:' . base64_encode(str_repeat('x', 40)), $account['id']]);

        $response = $this->request('POST', "/api/admin/github-accounts/{$account['id']}/test");

        $this->assertSame('needs_reentry', $this->json($response)['account']['status']);
    }

    public function test_connection_tests_are_rate_limited_to_ten_per_minute(): void
    {
        $account = $this->createAccount();

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(200, $this->request('POST', "/api/admin/github-accounts/{$account['id']}/test")->getStatusCode());
        }

        $this->assertSame(429, $this->request('POST', "/api/admin/github-accounts/{$account['id']}/test")->getStatusCode());
    }

    // ---- discovery --------------------------------------------------------

    public function test_discovery_lists_projects_with_tracking_state(): void
    {
        $account = $this->createAccount();
        $this->github->projects = [
            ['id' => 'PVT_1', 'number' => 1, 'title' => 'Alpha', 'closed' => false, 'url' => 'https://github.com/orgs/acme/projects/1', 'shortDescription' => 'First'],
            ['id' => 'PVT_2', 'number' => 2, 'title' => 'Beta', 'closed' => true, 'url' => null, 'shortDescription' => null],
        ];
        static::$pdo->prepare(
            "INSERT INTO `projects` (account_id, github_project_id, github_owner, github_repo, project_number, name)
             VALUES (?, 'PVT_1', 'acme', '', 1, 'Alpha')"
        )->execute([$account['id']]);

        $response = $this->request('GET', "/api/admin/github-accounts/{$account['id']}/projects");

        $this->assertSame(200, $response->getStatusCode());
        $projects = $this->json($response)['projects'];
        $this->assertCount(2, $projects);
        $this->assertTrue($projects[0]['tracked']);
        $this->assertNotNull($projects[0]['project_id']);
        $this->assertFalse($projects[1]['tracked']);
        $this->assertTrue($projects[1]['closed']);
    }

    public function test_discovery_github_failure_returns_502_and_updates_status(): void
    {
        $account = $this->createAccount();
        $this->github->failWith = new GitHubApiException(401, [], 'GitHub API request failed with HTTP 401');

        $response = $this->request('GET', "/api/admin/github-accounts/{$account['id']}/projects");

        $this->assertSame(502, $response->getStatusCode());
        $status = static::$pdo->query('SELECT `status` FROM `github_accounts`')->fetchColumn();
        $this->assertSame('invalid', $status);
    }

    public function test_discovery_of_unknown_account_returns_404(): void
    {
        $this->loginAsAdmin();

        $this->assertSame(404, $this->request('GET', '/api/admin/github-accounts/999999/projects')->getStatusCode());
    }

    // ---- audit log / leak check ------------------------------------------

    public function test_audit_log_endpoint_lists_entries_without_secrets(): void
    {
        $account = $this->createAccount();
        $this->request('PUT', "/api/admin/github-accounts/{$account['id']}", ['token' => self::TOKEN2]);

        $body = (string) $this->request('GET', '/api/admin/audit-log')->getBody();

        $this->assertStringContainsString('account.created', $body);
        $this->assertStringContainsString('account.token_replaced', $body);
        $this->assertStringNotContainsString(self::TOKEN, $body);
        $this->assertStringNotContainsString(self::TOKEN2, $body);
    }

    public function test_no_response_in_a_full_lifecycle_ever_contains_a_token(): void
    {
        $this->loginAsAdmin();
        $bodies = [];

        $create = $this->request('POST', '/api/admin/github-accounts', $this->validBody());
        $bodies[] = (string) $create->getBody();
        $id = $this->json($create)['account']['id'];

        foreach ([
            ['GET', '/api/admin/github-accounts'],
            ['PUT', "/api/admin/github-accounts/{$id}", ['token' => self::TOKEN2]],
            ['POST', "/api/admin/github-accounts/{$id}/test"],
            ['GET', "/api/admin/github-accounts/{$id}/projects"],
            ['GET', '/api/admin/audit-log'],
        ] as $call) {
            $bodies[] = (string) $this->request($call[0], $call[1], $call[2] ?? null)->getBody();
        }

        $all = implode("\n", $bodies);
        $this->assertStringNotContainsString(self::TOKEN, $all);
        $this->assertStringNotContainsString(self::TOKEN2, $all);
    }
}
