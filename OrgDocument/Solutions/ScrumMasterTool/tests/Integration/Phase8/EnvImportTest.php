<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Repositories\ProjectRepository;
use App\Services\CredentialVault;

/**
 * T053 — import of the legacy .env GitHub settings into an account + tracked project.
 */
final class EnvImportTest extends Phase8TestCase
{
    private const PAT = 'ghp_LEGACYTOKENLEGACYTOKENLEGACYTOKEN12';

    private ProjectRepository $projects;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projects = new ProjectRepository(static::$pdo);
        static::$pdo->exec('DELETE FROM `issues`');
        static::$pdo->exec('DELETE FROM `sync_history`');
        $this->github->projects = [['id' => 'PVT_kwTest01', 'number' => 3, 'title' => 'Sprint Board', 'closed' => false, 'url' => 'https://github.com/orgs/acme/projects/3', 'shortDescription' => '']];
    }

    private function configure(array $overrides = []): void
    {
        $this->useImporter($overrides + ['pat' => self::PAT, 'org' => 'acme', 'project_number' => 3]);
    }

    public function test_import_links_the_existing_project_and_keeps_its_id_and_data(): void
    {
        static::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name) VALUES ('PVT_kwTest01', 'acme', '', 3, 'Sprint Board')");
        $projectId = (int) static::$pdo->lastInsertId();
        static::$pdo->exec("INSERT INTO `issues` (github_issue_id, project_id, title, status) VALUES ('I_1', {$projectId}, 'Keep me', 'open')");
        $this->configure();
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/github-accounts/import-env');

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $body = $this->json($response);
        $this->assertTrue($body['account_created']);
        $this->assertTrue($body['project_linked']);
        $this->assertSame($projectId, $body['project_id']);
        $this->assertSame($body['account_id'], (int) static::$pdo->query("SELECT `account_id` FROM `projects` WHERE `id` = {$projectId}")->fetchColumn());
        $this->assertSame(1, (int) static::$pdo->query("SELECT COUNT(*) FROM `issues` WHERE `project_id` = {$projectId}")->fetchColumn());
        $this->assertSame('ok', $body['account']['status']);
        $this->assertSame('acme', $body['account']['owner_login']);
    }

    public function test_the_imported_token_is_encrypted_and_never_returned(): void
    {
        $this->configure();
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/github-accounts/import-env');

        $this->assertStringNotContainsString(self::PAT, (string) $response->getBody());
        $row = static::$pdo->query('SELECT * FROM `github_accounts`')->fetch();
        $this->assertStringNotContainsString(self::PAT, json_encode($row));
        $this->assertSame('N12', substr((string) $row['token_hint'], -3));
        $this->assertSame(self::PAT, static::$app->getContainer()->get(CredentialVault::class)->decrypt($row['token_ciphertext'], $row['token_nonce'] ?? ''));
        $audit = json_encode(static::$pdo->query('SELECT * FROM `admin_audit_log`')->fetchAll());
        $this->assertStringNotContainsString(self::PAT, $audit);
        $this->assertStringContainsString('account.imported_from_env', $audit);
    }

    public function test_fresh_install_tracks_the_project_found_on_github(): void
    {
        $this->configure();
        $this->loginAsAdmin();

        $body = $this->json($this->request('POST', '/api/admin/github-accounts/import-env'));

        $this->assertTrue($body['project_linked']);
        $project = $this->projects->findById((int) $body['project_id']);
        $this->assertSame('Sprint Board', $project['name']);
        $this->assertTrue($project['is_tracked']);
    }

    public function test_import_is_idempotent(): void
    {
        $this->configure();
        $this->loginAsAdmin();
        $first = $this->request('POST', '/api/admin/github-accounts/import-env');
        $second = $this->request('POST', '/api/admin/github-accounts/import-env');

        $this->assertSame(201, $first->getStatusCode());
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame('already_imported', $this->json($second)['status']);
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn());
    }

    public function test_import_does_not_overwrite_an_existing_accounts_token_or_relink_a_project(): void
    {
        $existing = $this->seedAccount('acme', 'ghp_ADMINENTEREDTOKENADMINENTEREDTOKEN');
        $other    = $this->seedAccount('other', 'ghp_OTHERACCOUNTTOKENOTHERACCOUNTTOKE');
        static::$pdo->exec("INSERT INTO `projects` (account_id, github_project_id, github_owner, github_repo, project_number, name) VALUES ({$other}, 'PVT_kwTest01', 'acme', '', 3, 'Sprint Board')");
        $before = static::$pdo->query("SELECT `token_ciphertext` FROM `github_accounts` WHERE `id` = {$existing}")->fetchColumn();
        $this->configure();
        $this->loginAsAdmin();

        $body = $this->json($this->request('POST', '/api/admin/github-accounts/import-env'));

        $this->assertFalse($body['account_created']);
        $this->assertFalse($body['project_linked']);
        $this->assertSame($existing, $body['account_id']);
        $this->assertSame($before, static::$pdo->query("SELECT `token_ciphertext` FROM `github_accounts` WHERE `id` = {$existing}")->fetchColumn());
        $this->assertSame($other, (int) static::$pdo->query("SELECT `account_id` FROM `projects`")->fetchColumn());
    }

    public function test_missing_legacy_values_return_422_and_create_nothing(): void
    {
        $this->configure(['pat' => '']);
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/github-accounts/import-env');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(['REPO_PAT'], $this->json($response)['missing']);
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_missing_app_key_returns_503(): void
    {
        $this->useImporter(['pat' => self::PAT, 'org' => 'acme', 'project_number' => 3], new CredentialVault(''));
        $this->loginAsAdmin();

        $this->assertSame(503, $this->request('POST', '/api/admin/github-accounts/import-env')->getStatusCode());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `github_accounts`')->fetchColumn());
    }

    public function test_project_missing_on_github_still_creates_the_account(): void
    {
        $this->github->projects = [];
        $this->configure();
        $this->loginAsAdmin();

        $body = $this->json($this->request('POST', '/api/admin/github-accounts/import-env'));

        $this->assertTrue($body['account_created']);
        $this->assertFalse($body['project_linked']);
        $this->assertNull($body['project_id']);
        $this->assertStringContainsString('not found', implode(' ', $body['messages']));
    }

    public function test_user_owned_projects_are_detected_as_user_accounts(): void
    {
        $this->github->ownerType = 'User';
        $this->configure();
        $this->loginAsAdmin();

        $body = $this->json($this->request('POST', '/api/admin/github-accounts/import-env'));

        $this->assertSame('user', $body['account']['owner_type']);
    }

    public function test_import_requires_an_admin(): void
    {
        $this->configure();

        $this->assertSame(401, $this->request('POST', '/api/admin/github-accounts/import-env')->getStatusCode());

        $this->authenticateSession($this->seedMember('member@test.local', 'Member1234!'));
        $this->assertSame(403, $this->request('POST', '/api/admin/github-accounts/import-env')->getStatusCode());
    }
}
