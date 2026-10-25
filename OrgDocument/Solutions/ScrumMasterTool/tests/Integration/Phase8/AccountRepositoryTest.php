<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Repositories\AuditLogRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;

/**
 * T046 — data layer for GitHub accounts, tracked projects and the audit log.
 */
final class AccountRepositoryTest extends Phase8TestCase
{
    private GitHubAccountRepository $accounts;
    private ProjectRepository $projects;
    private AuditLogRepository $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accounts = new GitHubAccountRepository(static::$pdo);
        $this->projects = new ProjectRepository(static::$pdo);
        $this->audit    = new AuditLogRepository(static::$pdo);
    }

    // ---- accounts ---------------------------------------------------------

    public function test_created_account_has_defaults_and_no_token(): void
    {
        $id  = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $row = $this->accounts->findPublicById($id);

        $this->assertSame('Acme', $row['label']);
        $this->assertSame('organization', $row['owner_type']);
        $this->assertSame('https://api.github.com/graphql', $row['graphql_url']);
        $this->assertSame('unverified', $row['status']);
        $this->assertFalse($row['has_token']);
        $this->assertSame(0, $row['tracked_projects']);
    }

    public function test_public_shape_never_contains_ciphertext_or_nonce(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->saveToken($id, 'CIPHERTEXT-VALUE', 'NONCE-VALUE', 'abcd');

        foreach ([$this->accounts->findPublicById($id), $this->accounts->findAllPublic()[0], $this->accounts->findByOwner('acme', 'https://api.github.com/graphql')] as $row) {
            $this->assertArrayNotHasKey('token_ciphertext', $row);
            $this->assertArrayNotHasKey('token_nonce', $row);
            $this->assertStringNotContainsString('CIPHERTEXT-VALUE', json_encode($row));
            $this->assertTrue($row['has_token']);
            $this->assertSame('abcd', $row['token_hint']);
        }
    }

    public function test_find_with_secret_returns_ciphertext_for_the_client_factory(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->saveToken($id, 'CIPHERTEXT-VALUE', 'NONCE-VALUE', 'abcd');

        $row = $this->accounts->findWithSecret($id);

        $this->assertSame('CIPHERTEXT-VALUE', $row['token_ciphertext']);
        $this->assertSame('NONCE-VALUE', $row['token_nonce']);
    }

    public function test_replacing_the_token_resets_the_status_to_unverified(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->saveToken($id, 'c1', 'n1', 'aaaa');
        $this->accounts->updateStatus($id, 'ok', 'Connected as bot', '2026-10-25 10:00:00', '2027-01-01 00:00:00');
        $this->assertSame('ok', $this->accounts->findPublicById($id)['status']);

        $this->accounts->saveToken($id, 'c2', 'n2', 'bbbb');

        $row = $this->accounts->findPublicById($id);
        $this->assertSame('unverified', $row['status']);
        $this->assertNull($row['last_verified_at']);
        $this->assertSame('bbbb', $row['token_hint']);
    }

    public function test_update_status_records_message_and_timestamps(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->updateStatus($id, 'insufficient_scope', 'Missing read:project', '2026-10-25 10:00:00', '2027-01-01 00:00:00');

        $row = $this->accounts->findPublicById($id);
        $this->assertSame('insufficient_scope', $row['status']);
        $this->assertSame('Missing read:project', $row['status_message']);
        $this->assertSame('2026-10-25 10:00:00', $row['last_verified_at']);
        $this->assertSame('2027-01-01 00:00:00', $row['token_expires_at']);
    }

    public function test_update_changes_only_allowed_columns(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->update($id, ['label' => 'Acme Corp', 'owner_login' => 'hacked', 'graphql_url' => 'https://ghe.example/graphql']);

        $row = $this->accounts->findPublicById($id);
        $this->assertSame('Acme Corp', $row['label']);
        $this->assertSame('https://ghe.example/graphql', $row['graphql_url']);
        $this->assertSame('acme', $row['owner_login'], 'owner_login is immutable through update()');
    }

    public function test_owner_and_endpoint_must_be_unique(): void
    {
        $this->accounts->create(['label' => 'One', 'owner_login' => 'acme']);

        $this->expectException(\PDOException::class);
        $this->accounts->create(['label' => 'Two', 'owner_login' => 'acme']);
    }

    public function test_same_owner_on_a_different_endpoint_is_allowed(): void
    {
        $this->accounts->create(['label' => 'One', 'owner_login' => 'acme']);
        $id = $this->accounts->create(['label' => 'Ent', 'owner_login' => 'acme', 'graphql_url' => 'https://ghe.example/graphql']);

        $this->assertGreaterThan(0, $id);
    }

    public function test_account_with_projects_cannot_be_deleted(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->projects->track([
            'account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme',
            'project_number' => 1, 'name' => 'Board',
        ]);

        $this->assertSame(1, $this->accounts->countProjects($accountId));
        $this->expectException(\PDOException::class);
        $this->accounts->delete($accountId);
    }

    public function test_account_without_projects_can_be_deleted(): void
    {
        $id = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $this->accounts->delete($id);

        $this->assertNull($this->accounts->findPublicById($id));
    }

    public function test_deleting_the_creator_keeps_the_account(): void
    {
        $userId = $this->seedMember('creator@test.local', 'Creator1234!', 'Creator');
        $id     = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme', 'created_by' => $userId]);

        static::$pdo->prepare('DELETE FROM `users` WHERE `id` = ?')->execute([$userId]);

        $this->assertNull($this->accounts->findPublicById($id)['created_by']);
    }

    // ---- projects ---------------------------------------------------------

    public function test_existing_project_rows_default_to_tracked_without_account(): void
    {
        static::$pdo->exec(
            "INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name)
             VALUES ('PVT_OLD', 'acme', '', 1, 'Legacy')"
        );

        $rows = $this->projects->findTracked();

        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['is_tracked']);
        $this->assertNull($rows[0]['account_id']);
        $this->assertNull($rows[0]['field_mapping']);
    }

    public function test_track_creates_and_is_idempotent(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $data = ['account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme', 'project_number' => 4, 'name' => 'Board'];

        $first  = $this->projects->track($data);
        $second = $this->projects->track($data + []);

        $this->assertSame($first, $second);
        $this->assertCount(1, $this->projects->findTracked());
        $this->assertSame(1, $this->projects->countTracked());
    }

    public function test_untracking_archives_and_retracking_restores(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $id = $this->projects->track(['account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme', 'project_number' => 1, 'name' => 'Board']);

        $this->projects->setTracked($id, false);
        $archived = $this->projects->findById($id);
        $this->assertFalse($archived['is_tracked']);
        $this->assertNotNull($archived['archived_at']);
        $this->assertCount(0, $this->projects->findTracked());
        $this->assertCount(0, $this->projects->findForApi(false));
        $this->assertCount(1, $this->projects->findForApi(true));

        $this->projects->setTracked($id, true);
        $restored = $this->projects->findById($id);
        $this->assertTrue($restored['is_tracked']);
        $this->assertNull($restored['archived_at']);
    }

    public function test_field_mapping_round_trips_and_can_be_cleared(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $id = $this->projects->track(['account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme', 'project_number' => 1, 'name' => 'Board']);

        $this->projects->setFieldMapping($id, ['estimated' => 'Est', 'iteration' => 'Sprint']);
        $this->assertSame(['estimated' => 'Est', 'iteration' => 'Sprint'], $this->projects->findById($id)['field_mapping']);

        $this->projects->setFieldMapping($id, null);
        $this->assertNull($this->projects->findById($id)['field_mapping']);
    }

    public function test_record_sync_status_and_api_listing_includes_account_label(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $id = $this->projects->track(['account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme', 'project_number' => 1, 'name' => 'Board']);

        $this->projects->recordSyncStatus($id, 'failed');

        $row = $this->projects->findForApi()[0];
        $this->assertSame('failed', $row['last_sync_status']);
        $this->assertNotNull($row['last_sync_at']);
        $this->assertSame('Acme', $row['account_label']);
    }

    public function test_delete_with_data_removes_issues_through_cascade(): void
    {
        $accountId = $this->accounts->create(['label' => 'Acme', 'owner_login' => 'acme']);
        $id = $this->projects->track(['account_id' => $accountId, 'github_project_id' => 'PVT_1', 'github_owner' => 'acme', 'project_number' => 1, 'name' => 'Board']);
        static::$pdo->prepare("INSERT INTO `issues` (project_id, github_issue_id, title, status) VALUES (?, 'I1', 'x', 'open')")->execute([$id]);

        $this->projects->deleteWithData($id);

        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `issues`')->fetchColumn());
    }

    // ---- audit log --------------------------------------------------------

    public function test_audit_entries_are_listed_newest_first_with_actor(): void
    {
        $actor = $this->seedMember('actor@test.local', 'Actor1234!', 'Actor');
        $this->audit->append($actor, 'account.created', 'github_account', 1, ['label' => 'Acme']);
        $this->audit->append($actor, 'account.deleted', 'github_account', 1);

        $rows = $this->audit->latest(10);

        $this->assertSame('account.deleted', $rows[0]['action']);
        $this->assertSame('Actor', $rows[0]['actor_name']);
        $this->assertSame(['label' => 'Acme'], $rows[1]['detail']);
    }

    public function test_audit_detail_never_stores_secret_keys(): void
    {
        $this->audit->append(null, 'account.updated', 'github_account', 1, [
            'label' => 'Acme', 'token' => 'ghp_SECRET', 'nested' => ['password' => 'pw', 'ok' => 1],
        ]);

        $raw = (string) static::$pdo->query('SELECT `detail` FROM `admin_audit_log`')->fetchColumn();

        $this->assertStringNotContainsString('ghp_SECRET', $raw);
        $this->assertStringNotContainsString('"pw"', $raw);
        $this->assertStringContainsString('Acme', $raw);
        $this->assertStringContainsString('"ok":1', $raw);
    }
}
