<?php

declare(strict_types=1);

namespace Tests\Integration\Phase6;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Integration\IntegrationTestCase;

/**
 * EndToEndTest — Phase 6 end-to-end acceptance tests (T032).
 *
 * Validates that all 13 API endpoints work correctly across the full
 * middleware stack: routing → auth → business logic → DB → JSON response.
 *
 * Test categories:
 *  1. Auth lifecycle (login / me / logout / session expiry)
 *  2. Member access control — can read, cannot write admin routes
 *  3. Admin access control — can read/write all routes
 *  4. All 13 endpoint smoke — correct status codes
 *  5. Time update flow — PUT /api/issues/{id}/time persists correctly
 *  6. Burndown data integrity — endpoint returns expected shape
 *  7. Efficiency data integrity — endpoint returns expected shape
 *  8. Sync history endpoint reflects recorded rows
 *
 * DB seeding:
 *  - setUpBeforeClass() from IntegrationTestCase boots the Slim app.
 *  - setUp() here truncates projects/issues/burndown_daily/sync_history, then
 *    inserts one project + two issues for tests that need data.
 *
 * Run with:
 *   vendor/phpunit/phpunit/phpunit --testsuite "Phase6 E2E"
 */
final class EndToEndTest extends IntegrationTestCase
{
    // IDs of seeded rows — populated in setUp() so each test gets a fresh state.
    private int $projectId;
    private int $issueId;
    private int $memberUserId;

    // =========================================================================
    // Per-test setup — seed a minimal dataset
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp(); // seeds admin user + resets session

        // Clean tables in FK-safe order
        static::$pdo->exec('DELETE FROM `burndown_daily`');
        static::$pdo->exec('DELETE FROM `time_logs`');
        static::$pdo->exec('DELETE FROM `sync_history`');
        static::$pdo->exec('DELETE FROM `issues`');
        static::$pdo->exec('DELETE FROM `projects`');

        // Seed a project
        $stmt = static::$pdo->prepare(
            'INSERT INTO `projects`
             (github_project_id, github_owner, github_repo, project_number, name, current_iteration)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(['PVT_TEST_001', 'test-owner', 'test-repo', 1, 'Test Project', 'Sprint 1']);
        $this->projectId = (int) static::$pdo->lastInsertId();

        // Seed two issues
        $stmt = static::$pdo->prepare(
            'INSERT INTO `issues`
             (project_id, github_issue_id, title, status, assignee, iteration, estimated_time, remaining_time, actual_time)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$this->projectId, 'I_OPEN_001', 'Open Issue', 'open', 'alice', 'Sprint 1', 8.0, 5.0, 3.0]);
        $this->issueId = (int) static::$pdo->lastInsertId();

        $stmt->execute([$this->projectId, 'I_CLOSED_001', 'Closed Issue', 'closed', 'alice', 'Sprint 1', 4.0, 0.0, 4.5]);

        // Seed a member user (role = member)
        $this->memberUserId = $this->seedMember('member@test.local', 'Member1234!', 'Test Member');
    }

    // =========================================================================
    // 1. Auth lifecycle
    // =========================================================================

    public function test_login_with_valid_credentials_returns_200(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('user', $body);
        $this->assertSame(static::$adminEmail, $body['user']['email']);
    }

    public function test_repeated_login_still_returns_200(): void
    {
        $this->login(static::$adminEmail, static::$adminPassword);
        $response = $this->login(static::$adminEmail, static::$adminPassword);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_logout_returns_200(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/auth/logout');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_me_after_logout_returns_401(): void
    {
        $this->loginAsAdmin();
        $this->request('POST', '/api/auth/logout');
        // session_unset() simulates cleared session
        session_unset();

        $response = $this->request('GET', '/api/auth/me');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_me_while_authenticated_returns_user_data(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/auth/me');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('user', $body);
        $this->assertSame(static::$adminEmail, $body['user']['email']);
        // Must never expose password_hash
        $this->assertStringNotContainsString('password_hash', json_encode($body));
        $this->assertStringNotContainsString('$2y$', json_encode($body));
    }

    public function test_unauthenticated_request_to_protected_route_returns_401(): void
    {
        // No login → session is empty
        $response = $this->request('GET', '/api/projects');

        $this->assertSame(401, $response->getStatusCode());
    }

    // =========================================================================
    // 2. Member access control
    // =========================================================================

    public function test_member_can_list_projects(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('GET', '/api/projects');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('projects', $body);
    }

    public function test_member_can_get_issues(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('GET', "/api/projects/{$this->projectId}/issues");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('issues', $body);
        $this->assertArrayHasKey('total', $body);
    }

    public function test_member_can_get_burndown(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('GET', "/api/projects/{$this->projectId}/burndown");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('project_id', $body);
        $this->assertArrayHasKey('points', $body);
    }

    public function test_member_can_get_members(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('GET', "/api/projects/{$this->projectId}/members");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('members', $body);
    }

    public function test_member_cannot_create_user_returns_403(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'new@example.com',
            'password'     => 'Password123!',
            'display_name' => 'New User',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_member_cannot_list_admin_users_returns_403(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('GET', '/api/admin/users');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_member_cannot_trigger_sync_returns_403(): void
    {
        $this->authenticateSession($this->memberUserId);
        $response = $this->request('POST', '/api/sync/trigger');

        $this->assertSame(403, $response->getStatusCode());
    }

    // =========================================================================
    // 3. Admin access control
    // =========================================================================

    public function test_admin_can_list_users(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/admin/users');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('users', $body);
        $this->assertIsArray($body['users']);
    }

    public function test_admin_can_create_user(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'newuser@example.com',
            'password'     => 'Secure5678!',
            'display_name' => 'New User',
            'role'         => 'member',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('user', $body);
        $this->assertSame('newuser@example.com', $body['user']['email']);
        // No password_hash in response
        $this->assertStringNotContainsString('password_hash', json_encode($body));
    }

    public function test_admin_create_duplicate_user_returns_409(): void
    {
        $this->loginAsAdmin();
        // Create once
        $this->request('POST', '/api/admin/users', [
            'email'        => 'dup@example.com',
            'password'     => 'Secure5678!',
            'display_name' => 'Dup User',
            'role'         => 'member',
        ]);
        // Create again — should conflict
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'dup@example.com',
            'password'     => 'Secure5678!',
            'display_name' => 'Dup User',
            'role'         => 'member',
        ]);

        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_admin_create_user_with_invalid_email_returns_422(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'not-an-email',
            'password'     => 'Secure5678!',
            'display_name' => 'Bad Email User',
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    // =========================================================================
    // 4. All 13 endpoint smoke — correct status codes when authenticated
    // =========================================================================

    /**
     * Verifies all 13 defined endpoints respond with an expected status code
     * (not 404 or 500) when called by an authenticated admin.
     *
     * Each entry: [ method, path_template, expected_status, body_template ]
     * Path templates may contain {PROJECT_ID} and {ISSUE_ID} which are
     * replaced with $this->projectId / $this->issueId at call time.
     */
    public function test_all_endpoints_respond(): void
    {
        $this->loginAsAdmin();

        $id  = $this->projectId;

        $specs = [
            ['GET',  '/api/auth/me',                                 200, null],
            ['POST', '/api/auth/logout',                             200, null],
            ['POST', '/api/auth/login', 200,
                ['email' => static::$adminEmail, 'password' => static::$adminPassword]],
            ['GET',  '/api/projects',                                200, null],
            ['GET',  "/api/projects/{$id}",                          200, null],
            ['GET',  "/api/projects/{$id}/issues",                   200, null],
            ['GET',  "/api/projects/{$id}/iterations",               200, null],
            ['GET',  "/api/projects/{$id}/burndown",                 200, null],
            ['GET',  "/api/projects/{$id}/members",                  200, null],
            ['GET',  '/api/sync/history',                            200, null],
            ['GET',  '/api/admin/users',                             200, null],
            ['POST', '/api/admin/users',                             201,
                ['email' => 'smoke13@test.local', 'password' => 'Smoke1234!', 'display_name' => 'Smoke', 'role' => 'member']],
        ];

        foreach ($specs as [$method, $path, $expected, $body]) {
            // Re-authenticate before each call in case a previous POST /logout cleared session
            $this->loginAsAdmin();
            $response = $this->request($method, $path, $body);
            $this->assertSame(
                $expected,
                $response->getStatusCode(),
                "Expected HTTP {$expected} for {$method} {$path}, "
                . "got {$response->getStatusCode()}: "
                . substr((string) $response->getBody(), 0, 200),
            );
        }
    }

    // =========================================================================
    // 5. Hours are read-only (synced from GitHub)
    // =========================================================================

    public function test_time_update_endpoint_is_retired(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('PUT', "/api/issues/{$this->issueId}/time", [
            'estimated_time' => 10.0,
        ]);

        $this->assertContains($response->getStatusCode(), [404, 405]);

        // Values are untouched
        $stmt = static::$pdo->prepare('SELECT `estimated_time` FROM `issues` WHERE `id` = ?');
        $stmt->execute([$this->issueId]);
        $this->assertEquals(8.0, (float) $stmt->fetchColumn());
    }

    // =========================================================================
    // 6. Burndown data integrity
    // =========================================================================

    public function test_burndown_returns_empty_points_when_no_snapshots(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/burndown");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertSame($this->projectId, $body['project_id']);
        $this->assertIsArray($body['points']);
    }

    public function test_burndown_returns_points_after_snapshot_inserted(): void
    {
        // Insert a burndown snapshot directly
        $stmt = static::$pdo->prepare(
            'INSERT INTO `burndown_daily`
             (project_id, iteration, snapshot_date, total_estimated, ideal_remaining, actual_remaining, open_count, closed_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$this->projectId, 'Sprint 1', '2026-04-06', 12.0, 12.0, 8.0, 1, 1]);

        $this->loginAsAdmin();
        $response = $this->request(
            'GET',
            "/api/projects/{$this->projectId}/burndown?iteration=Sprint+1"
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertSame('Sprint 1', $body['iteration']);
        $this->assertNotEmpty($body['points']);
        $point = $body['points'][0];
        $this->assertArrayHasKey('date',   $point);
        $this->assertArrayHasKey('ideal',  $point);
        $this->assertArrayHasKey('actual', $point);
    }

    public function test_burndown_for_nonexistent_project_returns_404(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/projects/999999/burndown');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertArrayHasKey('error', $this->json($response));
    }

    // =========================================================================
    // 7. Efficiency data integrity
    // =========================================================================

    public function test_members_returns_200_with_expected_shape(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/members");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('project_id',  $body);
        $this->assertArrayHasKey('members',     $body);
        $this->assertArrayHasKey('trend',       $body);
    }

    public function test_members_with_closed_issues_includes_assignee(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/members");

        $body    = $this->json($response);
        $members = $body['members'];

        // alice has a closed issue in Sprint 1 (seeded in setUp)
        $alice = array_values(array_filter($members, fn(array $m) => $m['member'] === 'alice'));
        $this->assertNotEmpty($alice, 'alice should appear in members because she has a closed issue');

        $record = $alice[0];
        $this->assertArrayHasKey('estimated',    $record);
        $this->assertArrayHasKey('actual',       $record);
        $this->assertArrayHasKey('ratio',        $record);
        $this->assertArrayHasKey('issues_count', $record);
    }

    // =========================================================================
    // 8. Sync history
    // =========================================================================

    public function test_sync_history_returns_200_with_data_key(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/sync/history');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function test_sync_history_reflects_inserted_records(): void
    {
        // Insert a sync history row directly
        $stmt = static::$pdo->prepare(
            'INSERT INTO `sync_history`
             (project_id, status, issues_added, issues_updated, issues_removed, graphql_points_used)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$this->projectId, 'success', 5, 2, 0, 15]);

        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/sync/history?project_id={$this->projectId}");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertNotEmpty($body['data']);
        $this->assertSame('success', $body['data'][0]['status']);
    }

    public function test_sync_history_without_project_id_includes_all_projects_and_failed_runs(): void
    {
        $stmt = static::$pdo->prepare(
            'INSERT INTO `sync_history` (project_id, status, error_message) VALUES (?, ?, ?)'
        );
        $stmt->execute([$this->projectId, 'success', '']);
        $stmt->execute([null, 'failed', 'GitHub API returned HTTP 401']);

        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/sync/history');

        $this->assertSame(200, $response->getStatusCode());
        $statuses = array_column($this->json($response)['data'], 'status');
        $this->assertContains('success', $statuses);
        $this->assertContains('failed', $statuses);
    }

    public function test_sync_trigger_as_admin_returns_200_409_or_502(): void
    {
        // POST /api/sync/trigger syncs the first tracked project. With no GitHub account or
        // tracked project it returns 409 (needs configuration); a failing GitHub call returns 502.
        // The critical assertion is that we do NOT get 401 or 403.
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/sync/trigger');

        $this->assertContains(
            $response->getStatusCode(),
            [200, 409, 502],
            'Admin sync trigger should return 200, 409 (not configured) or 502 (API error), not auth failure',
        );
    }

    // =========================================================================
    // 9. Projects endpoint shape
    // =========================================================================

    public function test_get_projects_returns_seeded_project(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/projects');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertNotEmpty($body['projects']);

        $project = $body['projects'][0];
        $this->assertArrayHasKey('id',   $project);
        $this->assertArrayHasKey('name', $project);
    }

    public function test_get_single_project_returns_counts(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}");

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('project',      $body);
        $this->assertArrayHasKey('open_count',   $body['project']);
        $this->assertArrayHasKey('closed_count', $body['project']);
        $this->assertSame(1, $body['project']['open_count']);
        $this->assertSame(1, $body['project']['closed_count']);
    }

    public function test_get_nonexistent_project_returns_404(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/projects/999999');

        $this->assertSame(404, $response->getStatusCode());
    }

    // =========================================================================
    // 10. Issues filtering
    // =========================================================================

    public function test_issues_can_be_filtered_by_status(): void
    {
        $this->loginAsAdmin();

        $openResponse   = $this->request('GET', "/api/projects/{$this->projectId}/issues?status=open");
        $closedResponse = $this->request('GET', "/api/projects/{$this->projectId}/issues?status=closed");

        $this->assertSame(200, $openResponse->getStatusCode());
        $this->assertSame(200, $closedResponse->getStatusCode());

        $openIssues   = $this->json($openResponse)['issues'];
        $closedIssues = $this->json($closedResponse)['issues'];

        $this->assertCount(1, $openIssues,   'Should have exactly 1 open issue');
        $this->assertCount(1, $closedIssues, 'Should have exactly 1 closed issue');
        $this->assertSame('open',   $openIssues[0]['status']);
        $this->assertSame('closed', $closedIssues[0]['status']);
    }

    public function test_issues_response_contains_time_fields(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/issues");

        $body  = $this->json($response);
        $issue = $body['issues'][0];

        $this->assertArrayHasKey('estimated_time', $issue);
        $this->assertArrayHasKey('remaining_time', $issue);
        $this->assertArrayHasKey('actual_time',    $issue);
    }

    // =========================================================================
    // 10b. Issues pagination (REQ-ADD-001)
    // =========================================================================

    /** Insert $count extra issues so the project has $count + 2 issues in total. */
    private function seedExtraIssues(int $count): void
    {
        $stmt = static::$pdo->prepare(
            'INSERT INTO `issues` (project_id, github_issue_id, title, status, assignee, iteration,
                                   estimated_time, remaining_time, actual_time)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1)'
        );
        for ($i = 1; $i <= $count; $i++) {
            $stmt->execute([$this->projectId, "I_EXTRA_{$i}", "Extra {$i}", 'open', 'bob', 'Sprint 2']);
        }
    }

    public function test_issues_are_paginated_with_meta_and_totals(): void
    {
        $this->seedExtraIssues(28); // 30 issues in total
        $this->loginAsAdmin();

        $body = $this->json($this->request('GET', "/api/projects/{$this->projectId}/issues?page_size=10&page=2&sort=id&dir=asc"));

        $this->assertCount(10, $body['issues']);
        $this->assertSame(['page' => 2, 'page_size' => 10, 'total' => 30, 'total_pages' => 3], $body['meta']);
        $this->assertSame(30, $body['total']);
        // Totals cover all 30 filtered issues, not just the page: 8+4 + 28*1
        $this->assertEquals(40.0, $body['totals']['estimated']);
        $this->assertContains('alice', $body['assignees']);
        $this->assertContains('bob', $body['assignees']);
    }

    public function test_issues_default_page_size_is_25(): void
    {
        $this->seedExtraIssues(28);
        $this->loginAsAdmin();

        $body = $this->json($this->request('GET', "/api/projects/{$this->projectId}/issues"));

        $this->assertCount(25, $body['issues']);
        $this->assertSame(2, $body['meta']['total_pages']);
    }

    public function test_issues_page_beyond_last_is_clamped(): void
    {
        $this->seedExtraIssues(8); // 10 issues
        $this->loginAsAdmin();

        $body = $this->json($this->request('GET', "/api/projects/{$this->projectId}/issues?page_size=10&page=99"));

        $this->assertSame(1, $body['meta']['page']);
        $this->assertCount(10, $body['issues']);
    }

    public function test_issues_filters_apply_across_pages_and_sort_is_stable(): void
    {
        $this->seedExtraIssues(28);
        $this->loginAsAdmin();

        $first  = $this->json($this->request('GET', "/api/projects/{$this->projectId}/issues?assignee=bob&page_size=10&page=1&sort=title&dir=asc"));
        $second = $this->json($this->request('GET', "/api/projects/{$this->projectId}/issues?assignee=bob&page_size=10&page=2&sort=title&dir=asc"));

        $this->assertSame(28, $first['meta']['total']);
        $ids = array_merge(array_column($first['issues'], 'id'), array_column($second['issues'], 'id'));
        $this->assertCount(20, array_unique($ids), 'Pages must not overlap');
    }

    #[DataProvider('invalidPagingProvider')]
    public function test_issues_invalid_paging_params_return_400(string $query): void
    {
        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/issues?{$query}");

        $this->assertSame(400, $response->getStatusCode());
    }

    /** @return array<string,array{string}> */
    public static function invalidPagingProvider(): array
    {
        return [
            'page size not allowed' => ['page_size=7'],
            'page size not numeric' => ['page_size=abc'],
            'page zero'             => ['page=0'],
            'unknown sort column'   => ['sort=password_hash'],
            'bad direction'         => ['dir=sideways'],
        ];
    }

    // =========================================================================
    // 10c. Iterations (REQ-CHG-002)
    // =========================================================================

    public function test_iterations_lists_project_iterations_with_current_flag(): void
    {
        static::$pdo->prepare('UPDATE `projects` SET `current_iteration` = ? WHERE `id` = ?')
            ->execute(['Sprint 2', $this->projectId]);
        static::$pdo->prepare(
            'INSERT INTO `project_iterations` (project_id, title, start_date, duration_days, is_completed)
             VALUES (?, ?, ?, ?, ?), (?, ?, ?, ?, ?)'
        )->execute([
            $this->projectId, 'Sprint 1', '2026-10-01', 14, 1,
            $this->projectId, 'Sprint 2', '2026-10-15', 14, 0,
        ]);
        $this->seedExtraIssues(1); // adds an issue in 'Sprint 2'

        $this->loginAsAdmin();
        $response = $this->request('GET', "/api/projects/{$this->projectId}/iterations");

        $this->assertSame(200, $response->getStatusCode());
        $names = array_column($this->json($response)['iterations'], 'name');
        $this->assertSame(['Sprint 2', 'Sprint 1'], $names, 'Newest iteration first, no duplicates');

        $byName = array_column($this->json($response)['iterations'], null, 'name');
        $this->assertTrue($byName['Sprint 2']['is_current']);
        $this->assertFalse($byName['Sprint 1']['is_current']);
        $this->assertTrue($byName['Sprint 1']['is_completed']);
    }

    public function test_iterations_includes_names_found_only_on_issues(): void
    {
        $this->loginAsAdmin(); // seeded issues use iteration 'Sprint 1', no project_iterations rows
        $body = $this->json($this->request('GET', "/api/projects/{$this->projectId}/iterations"));

        $this->assertSame(['Sprint 1'], array_column($body['iterations'], 'name'));
    }

    public function test_iterations_for_unknown_project_returns_404(): void
    {
        $this->loginAsAdmin();

        $this->assertSame(404, $this->request('GET', '/api/projects/999999/iterations')->getStatusCode());
    }
}
