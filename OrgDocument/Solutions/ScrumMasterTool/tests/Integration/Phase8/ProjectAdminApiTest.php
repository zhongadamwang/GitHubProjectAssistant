<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Exceptions\GitHubApiException;
use App\Repositories\ProjectRepository;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * T050 — tracked projects: track / untrack / mapping / validation / delete data.
 */
final class ProjectAdminApiTest extends Phase8TestCase
{
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = $this->seedAccount();
        $this->github->projects = [
            ['id' => 'PVT_1', 'number' => 1, 'title' => 'Alpha', 'closed' => false, 'url' => null, 'shortDescription' => null],
            ['id' => 'PVT_2', 'number' => 2, 'title' => 'Beta', 'closed' => false, 'url' => null, 'shortDescription' => null],
        ];
    }

    /** @return array<string,mixed> */
    private function track(int $number = 1): array
    {
        $this->loginAsAdmin();
        $response = $this->request('POST', '/api/admin/projects', ['account_id' => $this->accountId, 'number' => $number]);
        $this->assertContains($response->getStatusCode(), [200, 201], (string) $response->getBody());

        return $this->json($response);
    }

    // ---- access control ---------------------------------------------------

    /** @return array<string,array{string,string}> */
    public static function endpointProvider(): array
    {
        return [
            'track'    => ['POST', '/api/admin/projects'],
            'update'   => ['PUT', '/api/admin/projects/1'],
            'untrack'  => ['DELETE', '/api/admin/projects/1'],
            'data'     => ['DELETE', '/api/admin/projects/1/data'],
            'validate' => ['POST', '/api/admin/projects/1/validate-fields'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function test_endpoints_require_authentication(string $method, string $path): void
    {
        $this->assertSame(401, $this->request($method, $path)->getStatusCode());
    }

    #[DataProvider('endpointProvider')]
    public function test_endpoints_are_admin_only(string $method, string $path): void
    {
        $this->authenticateSession($this->seedMember('member@test.local', 'Member1234!'));

        $this->assertSame(403, $this->request($method, $path)->getStatusCode());
    }

    // ---- track ------------------------------------------------------------

    public function test_tracking_creates_the_project_with_account_and_name(): void
    {
        $body = $this->track(2);

        $this->assertSame('Beta', $body['project']['name']);
        $this->assertSame($this->accountId, $body['project']['account_id']);
        $this->assertTrue($body['project']['is_tracked']);
        $this->assertSame(2, $body['project']['project_number']);
        $this->assertArrayNotHasKey('warning', $body);
    }

    public function test_tracking_the_same_project_twice_is_idempotent(): void
    {
        $first  = $this->track();
        $second = $this->track();

        $this->assertSame($first['project']['id'], $second['project']['id']);
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn());
        $actions = static::$pdo->query("SELECT COUNT(*) FROM `admin_audit_log` WHERE `action` = 'project.tracked'")->fetchColumn();
        $this->assertSame(1, (int) $actions, 'Only the first track is audited');
    }

    public function test_unknown_project_number_returns_404(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/projects', ['account_id' => $this->accountId, 'number' => 99]);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_tracking_validates_input_and_account(): void
    {
        $this->loginAsAdmin();

        $this->assertSame(422, $this->request('POST', '/api/admin/projects', ['account_id' => 0, 'number' => 1])->getStatusCode());
        $this->assertSame(404, $this->request('POST', '/api/admin/projects', ['account_id' => 999999, 'number' => 1])->getStatusCode());
    }

    public function test_github_failure_while_tracking_returns_502_and_flags_the_account(): void
    {
        $this->github->failWith = new GitHubApiException(401, [], 'GitHub API request failed with HTTP 401');
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/projects', ['account_id' => $this->accountId, 'number' => 1]);

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame('invalid', static::$pdo->query('SELECT `status` FROM `github_accounts`')->fetchColumn());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn());
    }

    public function test_tracking_more_than_ten_projects_warns_but_is_not_blocked(): void
    {
        $this->github->projects = [];
        for ($i = 1; $i <= 11; $i++) {
            $this->github->projects[] = ['id' => "PVT_{$i}", 'number' => $i, 'title' => "P{$i}", 'closed' => false, 'url' => null, 'shortDescription' => null];
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->assertArrayNotHasKey('warning', $this->track($i));
        }
        $eleventh = $this->track(11);

        $this->assertArrayHasKey('warning', $eleventh);
        $this->assertStringContainsString('10 projects', $eleventh['warning']);
        $this->assertSame(11, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects` WHERE `is_tracked` = 1')->fetchColumn());
    }

    // ---- update / mapping -------------------------------------------------

    public function test_field_mapping_can_be_set_and_cleared(): void
    {
        $id = $this->track()['project']['id'];

        $set = $this->json($this->request('PUT', "/api/admin/projects/{$id}", ['field_mapping' => ['estimated' => ' Est ', 'iteration' => 'Sprint', 'actual' => '']]));
        $this->assertSame(['estimated' => 'Est', 'iteration' => 'Sprint'], $set['project']['field_mapping']);

        $cleared = $this->json($this->request('PUT', "/api/admin/projects/{$id}", ['field_mapping' => null]));
        $this->assertNull($cleared['project']['field_mapping']);
    }

    /** @return array<string,array{array<string,mixed>,string}> */
    public static function invalidUpdateProvider(): array
    {
        return [
            'unknown role'   => [['field_mapping' => ['password' => 'x']], 'password'],
            'non-text name'  => [['field_mapping' => ['estimated' => ['a']]], 'estimated'],
            'name too long'  => [['field_mapping' => ['estimated' => str_repeat('x', 101)]], 'estimated'],
            'mapping scalar' => [['field_mapping' => 'x'], 'field_mapping'],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    #[DataProvider('invalidUpdateProvider')]
    public function test_update_validates_input(array $payload, string $field): void
    {
        $id = $this->track()['project']['id'];

        $response = $this->request('PUT', "/api/admin/projects/{$id}", $payload);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey($field, $this->json($response)['errors']);
    }

    public function test_update_unknown_project_returns_404(): void
    {
        $this->loginAsAdmin();

        $this->assertSame(404, $this->request('PUT', '/api/admin/projects/999999', ['is_tracked' => true])->getStatusCode());
    }

    public function test_update_ignores_name_because_github_owns_it(): void
    {
        $project = $this->track()['project'];

        $updated = $this->json($this->request('PUT', "/api/admin/projects/{$project['id']}", ['name' => 'Hacked']));

        $this->assertSame($project['name'], $updated['project']['name']);
    }

    // ---- untrack / retrack / delete data ----------------------------------

    public function test_untracking_archives_and_keeps_the_data(): void
    {
        $id = $this->track()['project']['id'];
        static::$pdo->prepare("INSERT INTO `issues` (project_id, github_issue_id, title, status) VALUES (?, 'I1', 'x', 'open')")->execute([$id]);

        $response = $this->request('DELETE', "/api/admin/projects/{$id}");

        $this->assertSame(200, $response->getStatusCode());
        $project = $this->json($response)['project'];
        $this->assertFalse($project['is_tracked']);
        $this->assertNotNull($project['archived_at']);
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `issues`')->fetchColumn());
    }

    public function test_archived_projects_are_hidden_from_the_project_list_except_for_admins_who_ask(): void
    {
        $id = $this->track()['project']['id'];
        $this->request('DELETE', "/api/admin/projects/{$id}");

        $default  = $this->json($this->request('GET', '/api/projects'))['projects'];
        $archived = $this->json($this->request('GET', '/api/projects?include_archived=1'))['projects'];

        $this->assertCount(0, $default);
        $this->assertCount(1, $archived);
        $this->assertFalse($archived[0]['is_tracked']);
    }

    public function test_members_never_see_archived_projects_even_when_asking(): void
    {
        $id = $this->track()['project']['id'];
        $this->request('DELETE', "/api/admin/projects/{$id}");

        $this->authenticateSession($this->seedMember('member@test.local', 'Member1234!'));
        $projects = $this->json($this->request('GET', '/api/projects?include_archived=1'))['projects'];

        $this->assertCount(0, $projects);
    }

    public function test_retracking_restores_an_archived_project_with_its_data(): void
    {
        $id = $this->track()['project']['id'];
        static::$pdo->prepare("INSERT INTO `issues` (project_id, github_issue_id, title, status) VALUES (?, 'I1', 'x', 'open')")->execute([$id]);
        $this->request('DELETE', "/api/admin/projects/{$id}");

        $again = $this->track();

        $this->assertSame($id, $again['project']['id']);
        $this->assertTrue($again['project']['is_tracked']);
        $this->assertNull($again['project']['archived_at']);
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `issues`')->fetchColumn());
    }

    public function test_deleting_data_requires_archiving_and_the_project_name(): void
    {
        $id = $this->track()['project']['id'];

        $stillTracked = $this->request('DELETE', "/api/admin/projects/{$id}/data", ['confirm' => 'Alpha']);
        $this->assertSame(409, $stillTracked->getStatusCode());

        $this->request('DELETE', "/api/admin/projects/{$id}");

        $wrongName = $this->request('DELETE', "/api/admin/projects/{$id}/data", ['confirm' => 'nope']);
        $this->assertSame(422, $wrongName->getStatusCode());
        $this->assertSame(1, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn());

        $ok = $this->request('DELETE', "/api/admin/projects/{$id}/data", ['confirm' => 'Alpha']);
        $this->assertSame(204, $ok->getStatusCode());
        $this->assertSame(0, (int) static::$pdo->query('SELECT COUNT(*) FROM `projects`')->fetchColumn());
    }

    public function test_project_api_exposes_account_and_sync_state(): void
    {
        $id = $this->track()['project']['id'];
        (new ProjectRepository(static::$pdo))->recordSyncStatus($id, 'ok');

        $project = $this->json($this->request('GET', '/api/projects'))['projects'][0];

        $this->assertSame('Acme', $project['account_label']);
        $this->assertSame('ok', $project['account_status']);
        $this->assertSame('ok', $project['last_sync_status']);
        $this->assertNotNull($project['last_sync_at']);
    }

    // ---- validate fields --------------------------------------------------

    public function test_field_validation_reports_found_missing_and_wrong_type(): void
    {
        $id = $this->track()['project']['id'];
        $this->github->projectFields = [
            ['id' => 'F1', 'name' => 'estimate hours', 'dataType' => 'NUMBER'],   // case-insensitive match
            ['id' => 'F2', 'name' => 'Remains Hours', 'dataType' => 'TEXT'],      // wrong type
            ['id' => 'F3', 'name' => 'Iteration', 'dataType' => 'ITERATION'],
        ];

        $response = $this->request('POST', "/api/admin/projects/{$id}/validate-fields");

        $this->assertSame(200, $response->getStatusCode());
        $body    = $this->json($response);
        $byRole  = array_column($body['fields'], null, 'role');
        $this->assertSame('found', $byRole['estimated']['status']);
        $this->assertSame('wrong_type', $byRole['remaining']['status']);
        $this->assertSame('TEXT', $byRole['remaining']['data_type']);
        $this->assertSame('missing', $byRole['actual']['status']);
        $this->assertSame('found', $byRole['iteration']['status']);
        $this->assertFalse($body['ok']);
        $this->assertSame('Estimate Hours', $body['mapping']['estimated']);
    }

    public function test_field_validation_uses_the_project_override(): void
    {
        $id = $this->track()['project']['id'];
        $this->request('PUT', "/api/admin/projects/{$id}", ['field_mapping' => ['estimated' => 'Est', 'remaining' => 'Rem', 'actual' => 'Act', 'iteration' => 'Sprint']]);
        $this->github->projectFields = [
            ['id' => 'F1', 'name' => 'Est', 'dataType' => 'NUMBER'],
            ['id' => 'F2', 'name' => 'Rem', 'dataType' => 'NUMBER'],
            ['id' => 'F3', 'name' => 'Act', 'dataType' => 'NUMBER'],
            ['id' => 'F4', 'name' => 'Sprint', 'dataType' => 'ITERATION'],
        ];

        $body = $this->json($this->request('POST', "/api/admin/projects/{$id}/validate-fields"));

        $this->assertTrue($body['ok']);
        $this->assertSame('Sprint', $body['mapping']['iteration']);
    }

    public function test_field_validation_of_unlinked_project_returns_409(): void
    {
        static::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name) VALUES ('PVT_L', 'acme', '', 9, 'Legacy')");
        $id = (int) static::$pdo->lastInsertId();
        $this->loginAsAdmin();

        $this->assertSame(409, $this->request('POST', "/api/admin/projects/{$id}/validate-fields")->getStatusCode());
    }
}
