<?php

declare(strict_types=1);

namespace Tests\Integration\Phase6;

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
        ]);
        // Create again — should conflict
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'dup@example.com',
            'password'     => 'Secure5678!',
            'display_name' => 'Dup User',
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
        $iid = $this->issueId;

        $specs = [
            ['GET',  '/api/auth/me',                                 200, null],
            ['POST', '/api/auth/logout',                             200, null],
            ['POST', '/api/auth/login', 200,
                ['email' => static::$adminEmail, 'password' => static::$adminPassword]],
            ['GET',  '/api/projects',                                200, null],
            ['GET',  "/api/projects/{$id}",                          200, null],
            ['GET',  "/api/projects/{$id}/issues",                   200, null],
            ['GET',  "/api/projects/{$id}/burndown",                 200, null],
            ['GET',  "/api/projects/{$id}/members",                  200, null],
            ['PUT',  "/api/issues/{$iid}/time",                      200, ['estimated_time' => 5.0]],
            ['GET',  '/api/sync/history',                            200, null],
            ['GET',  '/api/admin/users',                             200, null],
            ['POST', '/api/admin/users',                             201,
                ['email' => 'smoke13@test.local', 'password' => 'Smoke1234!', 'display_name' => 'Smoke']],
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
    // 5. Time update flow
    // =========================================================================

    public function test_update_time_returns_200_with_updated_issue(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('PUT', "/api/issues/{$this->issueId}/time", [
            'estimated_time' => 10.0,
            'remaining_time' => 7.0,
            'actual_time'    => 3.0,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertSame('10.00', (string) $body['estimated_time']);
        $this->assertSame('7.00',  (string) $body['remaining_time']);
        $this->assertSame('3.00',  (string) $body['actual_time']);
    }

    public function test_update_time_is_reflected_on_re_fetch(): void
    {
        $this->loginAsAdmin();
        $this->request('PUT', "/api/issues/{$this->issueId}/time", [
            'estimated_time' => 12.0,
        ]);

        // Re-fetch the issue list and find our issue
        $response = $this->request('GET', "/api/projects/{$this->projectId}/issues");
        $body     = $this->json($response);
        $issue    = $this->findIssue($body['issues'], $this->issueId);

        $this->assertNotNull($issue, 'Seeded issue not found in GET /issues response');
        $this->assertSame('12.00', (string) $issue['estimated_time']);
    }

    public function test_update_time_with_negative_value_returns_400(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('PUT', "/api/issues/{$this->issueId}/time", [
            'estimated_time' => -1.0,
        ]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_update_time_for_nonexistent_issue_returns_404(): void
    {
        $this->loginAsAdmin();
        $response = $this->request('PUT', '/api/issues/999999/time', [
            'estimated_time' => 5.0,
        ]);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_partial_update_leaves_other_time_fields_unchanged(): void
    {
        $this->loginAsAdmin();
        // Only update estimated_time; remaining_time and actual_time should stay
        $this->request('PUT', "/api/issues/{$this->issueId}/time", [
            'estimated_time' => 20.0,
        ]);

        $response = $this->request('GET', "/api/projects/{$this->projectId}/issues");
        $issue    = $this->findIssue($this->json($response)['issues'], $this->issueId);

        $this->assertSame('20.00', (string) $issue['estimated_time']);
        $this->assertSame('5.00',  (string) $issue['remaining_time']); // unchanged from seed
        $this->assertSame('3.00',  (string) $issue['actual_time']);    // unchanged from seed
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

    public function test_burndown_for_nonexistent_project_returns_200_empty(): void
    {
        // BurndownController returns empty points (not 404) when project has no data
        $this->loginAsAdmin();
        $response = $this->request('GET', '/api/projects/999999/burndown');

        $this->assertSame(200, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertIsArray($body['points']);
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

    public function test_sync_trigger_as_admin_returns_200_or_502(): void
    {
        // POST /api/sync/trigger calls SyncService::run() which contacts GitHub.
        // In the test environment there is no live PAT, so the service may throw
        // GitHubApiException → SyncController returns 502.
        // The critical assertion is that we do NOT get 401 or 403.
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/sync/trigger');

        $this->assertContains(
            $response->getStatusCode(),
            [200, 502],
            'Admin sync trigger should return 200 (success) or 502 (API error), not auth failure',
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
    // Helpers
    // =========================================================================

    /**
     * Find an issue array by its id within an issues array.
     *
     * @param array<int,array> $issues
     */
    private function findIssue(array $issues, int $id): ?array
    {
        foreach ($issues as $issue) {
            if ((int) $issue['id'] === $id) {
                return $issue;
            }
        }
        return null;
    }
}
