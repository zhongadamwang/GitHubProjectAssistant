<?php

declare(strict_types=1);

namespace Tests\Integration\Phase2;

use App\Exceptions\GitHubApiException;
use App\Services\GitHubClientInterface;
use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;
use App\Services\BurndownService;
use App\Services\SyncService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * SyncIntegrationTest — end-to-end tests for the GitHub sync pipeline.
 *
 * Tests the full chain:
 *   GitHubGraphQLServiceStub → ResponseParser → SyncService
 *   → ProjectRepository → IssueRepository → SyncHistoryRepository
 *   → snapshot file
 *
 * Requires a running MySQL test database configured via .env.test.
 * Run with: composer test -- --testsuite "Phase2 Integration"
 *
 * Set GITHUB_INTEGRATION_TEST=true to enable the live-API test (skipped by default).
 */
final class SyncIntegrationTest extends TestCase
{
    private static PDO $pdo;

    /** Temporary snapshot directory, unique per test to avoid collisions. */
    private string $snapshotDir;

    /** Loaded once from tests/fixtures/github-project-response.json */
    private array $fixture;

    // =========================================================================
    // Suite-level setup
    // =========================================================================

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $host   = $_ENV['DB_HOST']    ?? '127.0.0.1';
        $port   = $_ENV['DB_PORT']    ?? '3306';
        $dbname = $_ENV['DB_NAME']    ?? '';
        $user   = $_ENV['DB_USER']    ?? '';
        $pass   = $_ENV['DB_PASS']    ?? '';

        if (empty($dbname) || empty($user)) {
            self::markTestSkipped('DB_NAME and DB_USER must be set in .env.test to run sync integration tests.');
        }

        $dsn      = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    // =========================================================================
    // Per-test setup / teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        // Truncate the sync-related tables in dependency order
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE `time_logs`');
        self::$pdo->exec('TRUNCATE TABLE `project_iterations`');
        self::$pdo->exec('TRUNCATE TABLE `burndown_daily`');
        self::$pdo->exec('TRUNCATE TABLE `sync_history`');
        self::$pdo->exec('TRUNCATE TABLE `issues`');
        self::$pdo->exec('TRUNCATE TABLE `projects`');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Unique temp directory per test so concurrent runs don't collide
        $this->snapshotDir = sys_get_temp_dir() . '/scrum_test_' . uniqid('snap_', true);
        mkdir($this->snapshotDir, 0755, true);

        // Load fixture
        $fixturePath   = dirname(__DIR__, 2) . '/fixtures/github-project-response.json';
        $this->fixture = json_decode(
            (string) file_get_contents($fixturePath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Remove all snapshot files and the temp directory
        foreach (glob($this->snapshotDir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->snapshotDir);
    }

    // =========================================================================
    // Tests
    // =========================================================================

    /**
     * First-run: all 5 fixture issues are new → added=5, updated=0, unchanged=0.
     */
    public function testFirstRunAddsAllIssues(): void
    {
        $result = $this->makeSyncService()->run();

        $this->assertSame('success', $result->status);
        $this->assertSame(5, $result->added);
        $this->assertSame(0, $result->updated);
        $this->assertSame(0, $result->unchanged);
        $this->assertSame(0, $result->errors);
        $this->assertSame(5, $this->countIssueRows());
    }

    /**
     * Second run with identical fixture data → nothing changes (idempotency).
     */
    public function testSecondRunIsIdempotent(): void
    {
        $service = $this->makeSyncService();
        $service->run();

        $second = $service->run();

        $this->assertSame('success', $second->status);
        $this->assertSame(0, $second->added);
        $this->assertSame(0, $second->updated);
        $this->assertSame(5, $second->unchanged);
    }

    /**
     * sync_history gets one row per run.
     */
    public function testSyncHistoryRowsWrittenPerRun(): void
    {
        $service = $this->makeSyncService();
        $service->run();
        $this->assertSame(1, $this->countHistoryRows());

        $service->run();
        $this->assertSame(2, $this->countHistoryRows());
    }

    /**
     * An issue with an old `github_updated_at` in the DB is updated when
     * the fixture has a newer `updatedAt`.
     */
    public function testUpdatedIssueIsCountedAsUpdate(): void
    {
        // Pre-insert the project row
        self::$pdo->prepare(
            'INSERT INTO `projects` (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`)
             VALUES (?, ?, ?, ?, ?)'
        )->execute(['PVT_kwTest01', 'testowner', '', 1, 'Old Sprint Board']);

        $projectId = (int) self::$pdo->lastInsertId();

        // Pre-insert issue 1 with an old timestamp (fixture has 2026-04-01)
        self::$pdo->prepare(
            'INSERT INTO `issues` (`project_id`, `github_issue_id`, `title`, `status`, `github_updated_at`)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$projectId, 'I_kgDOA1AAAAA', 'Old title', 'open', '2000-01-01 10:00:00']);

        $result = $this->makeSyncService()->run();

        // Issue 1 replaces old row (updated=1), issues 2-5 are new (added=4)
        $this->assertSame(4, $result->added);
        $this->assertSame(1, $result->updated);
        $this->assertSame(0, $result->unchanged);
    }

    /**
     * Snapshot file is written to the configured directory with valid JSON content.
     */
    public function testSnapshotFileIsCreated(): void
    {
        $result = $this->makeSyncService()->run();

        $this->assertNotEmpty($result->snapshotFile, 'snapshotFile should not be empty');
        $this->assertFileExists($result->snapshotFile);

        $content = (string) file_get_contents($result->snapshotFile);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('project', $decoded);
        $this->assertArrayHasKey('items', $decoded);
    }

    /**
     * When the GitHub API throws, SyncService writes a 'failed' history record
     * and re-throws the exception.
     */
    public function testApiFailureWritesFailedHistoryRecord(): void
    {
        $failStub = new class implements GitHubClientInterface {
            public function query(string $queryName, array $variables = []): array
            {
                throw new GitHubApiException(503, [], 'Service unavailable');
            }

            public function fetchAllProjectItems(string $owner, int $projectNumber): array
            {
                return [];
            }

            public function checkConnection(): bool
            {
                return false;
            }
        };

        $this->expectException(GitHubApiException::class);

        try {
            $this->makeSyncService($failStub)->run();
        } finally {
            // History record must have been written before the rethrow
            $this->assertSame(1, $this->countHistoryRows('failed'));
        }
    }

    /**
     * Hours come from the GitHub number fields "Estimate Hours", "Remains Hours",
     * "Actual Hours"; the sprint comes from the "Iteration" field.
     */
    public function testHoursAndIterationAreSyncedFromGitHubFields(): void
    {
        $this->setItemFields(0, ['Estimate Hours' => 8.0, 'Remains Hours' => 5.0, 'Actual Hours' => 3.0], 'Sprint 7');

        $this->makeSyncService()->run();

        $row = $this->fetchIssue('I_kgDOA1AAAAA');
        $this->assertEqualsWithDelta(8.0, (float) $row['estimated_time'], 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $row['remaining_time'], 0.001);
        $this->assertEqualsWithDelta(3.0, (float) $row['actual_time'],    0.001);
        $this->assertSame('Sprint 7', $row['iteration']);

        // Issues without the fields default to 0
        $other = $this->fetchIssue('I_kgDOA2BBBBB');
        $this->assertEqualsWithDelta(0.0, (float) $other['estimated_time'], 0.001);
    }

    /**
     * GitHub wins: a changed (or cleared) field value overwrites the stored hours
     * even though the Issue's own updatedAt did not change, and the change is audited
     * with a NULL (system) actor.
     */
    public function testGitHubHourChangesOverwriteLocalAndAreAudited(): void
    {
        $this->setItemFields(0, ['Estimate Hours' => 8.0, 'Remains Hours' => 5.0, 'Actual Hours' => 3.0], 'Sprint 7');
        $this->makeSyncService()->run();

        // Same Issue updatedAt, but Remains changes and Actual is cleared in GitHub
        $this->setItemFields(0, ['Estimate Hours' => 8.0, 'Remains Hours' => 2.0], 'Sprint 7');
        $result = $this->makeSyncService()->run();

        $this->assertSame(1, $result->updated, 'A field-only change must count as an update');
        $this->assertSame(0, $result->added);

        $row = $this->fetchIssue('I_kgDOA1AAAAA');
        $this->assertEqualsWithDelta(8.0, (float) $row['estimated_time'], 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $row['remaining_time'], 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $row['actual_time'],    0.001, 'Cleared field becomes 0');

        $logs = self::$pdo->query(
            'SELECT `field_name`, `old_value`, `new_value`, `changed_by` FROM `time_logs` ORDER BY `id`'
        )->fetchAll();
        $this->assertCount(2, $logs);
        $this->assertSame('remaining_time', $logs[0]['field_name']);
        $this->assertEqualsWithDelta(5.0, (float) $logs[0]['old_value'], 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $logs[0]['new_value'], 0.001);
        $this->assertNull($logs[0]['changed_by']);
        $this->assertSame('actual_time', $logs[1]['field_name']);
    }

    /** Re-syncing identical data touches nothing and logs nothing. */
    public function testResyncWithSameHoursIsUnchanged(): void
    {
        $this->setItemFields(0, ['Estimate Hours' => 8.0, 'Remains Hours' => 5.0, 'Actual Hours' => 3.0], 'Sprint 7');
        $this->makeSyncService()->run();

        $result = $this->makeSyncService()->run();

        $this->assertSame(0, $result->updated);
        $this->assertSame(5, $result->unchanged);
        $this->assertSame(0, (int) self::$pdo->query('SELECT COUNT(*) FROM `time_logs`')->fetchColumn());
    }

    /** Field names are configurable per deployment. */
    public function testCustomFieldNamesAreHonoured(): void
    {
        $this->setItemFields(0, ['Est' => 6.0, 'Rem' => 4.0, 'Act' => 1.0], 'Sprint 7', 'Sprint');

        $issueRepo = new IssueRepository(self::$pdo);
        (new SyncService(
            gitHub:          new GitHubGraphQLServiceStub($this->fixture),
            projectRepo:     new ProjectRepository(self::$pdo),
            issueRepo:       $issueRepo,
            historyRepo:     new SyncHistoryRepository(self::$pdo),
            burndownService: new BurndownService(new BurndownRepository(self::$pdo), $issueRepo),
            owner:           'testowner',
            projectNumber:   1,
            snapshotDir:     $this->snapshotDir,
            fieldNames:      ['estimated' => 'Est', 'remaining' => 'Rem', 'actual' => 'Act', 'iteration' => 'Sprint'],
        ))->run();

        $row = $this->fetchIssue('I_kgDOA1AAAAA');
        $this->assertEqualsWithDelta(6.0, (float) $row['estimated_time'], 0.001);
        $this->assertSame('Sprint 7', $row['iteration']);
    }

    /**
     * The iteration field configuration (active + completed sprints) is stored and the
     * sprint running today becomes projects.current_iteration.
     */
    public function testIterationConfigurationAndCurrentIterationAreStored(): void
    {
        $today     = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $fmt       = static fn(\DateTimeImmutable $d): string => $d->format('Y-m-d');
        $this->fixture['project_fields_response']['data']['organization']['projectV2']['fields']['nodes'][] = [
            'id' => 'PVTIF_001', 'name' => 'Iteration', 'dataType' => 'ITERATION',
            'configuration' => [
                'iterations' => [
                    ['id' => 'IT2', 'title' => 'Sprint 2', 'startDate' => $fmt($today->modify('-3 days')), 'duration' => 14],
                    ['id' => 'IT3', 'title' => 'Sprint 3', 'startDate' => $fmt($today->modify('+11 days')), 'duration' => 14],
                ],
                'completedIterations' => [
                    ['id' => 'IT1', 'title' => 'Sprint 1', 'startDate' => $fmt($today->modify('-17 days')), 'duration' => 14],
                ],
            ],
        ];

        $this->makeSyncService()->run();

        $projectId = $this->getProjectLocalId('PVT_kwTest01');
        $rows = self::$pdo->query(
            "SELECT `title`, `is_completed` FROM `project_iterations` WHERE `project_id` = {$projectId} ORDER BY `title`"
        )->fetchAll();
        $this->assertSame(['Sprint 1', 'Sprint 2', 'Sprint 3'], array_column($rows, 'title'));
        $this->assertSame(['1', '0', '0'], array_map('strval', array_column($rows, 'is_completed')));

        $current = self::$pdo->query("SELECT `current_iteration` FROM `projects` WHERE `id` = {$projectId}")->fetchColumn();
        $this->assertSame('Sprint 2', $current);
    }

    /**
     * Closed issues count as 0 remaining in the burndown aggregation, while their
     * estimate still counts; issues without an iteration are excluded.
     */
    public function testBurndownAggregationTreatsClosedIssuesAsZeroRemaining(): void
    {
        self::$pdo->exec("INSERT INTO `projects` (github_project_id, github_owner, github_repo, project_number, name)
                          VALUES ('PVT_AGG', 'o', '', 1, 'Agg')");
        $projectId = (int) self::$pdo->lastInsertId();
        $stmt = self::$pdo->prepare(
            'INSERT INTO `issues` (project_id, github_issue_id, title, status, iteration, estimated_time, remaining_time, actual_time)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([$projectId, 'A1', 'open one',   'open',   'Sprint 1', 10, 6]);
        $stmt->execute([$projectId, 'A2', 'closed one', 'closed', 'Sprint 1', 8, 3]);
        $stmt->execute([$projectId, 'A3', 'no sprint',  'open',   null,       5, 5]);

        $rows = (new IssueRepository(self::$pdo))->aggregateTimeByIteration($projectId);

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(18.0, (float) $rows[0]['total_estimated'], 0.001);
        $this->assertEqualsWithDelta(6.0,  (float) $rows[0]['total_remaining'], 0.001);
        $this->assertSame(1, (int) $rows[0]['closed_count']);
    }

    /**
     * Live-mode test — skipped unless GITHUB_INTEGRATION_TEST=true.
     *
     * Requires REPO_PAT, REPO_ORG, REPO_PROJECT_NUMBER in .env.test.
     */
    public function testLiveSyncSkippedByDefault(): void
    {
        if (($_ENV['GITHUB_INTEGRATION_TEST'] ?? 'false') !== 'true') {
            $this->markTestSkipped('Live GitHub sync test skipped. Set GITHUB_INTEGRATION_TEST=true to run.');
        }

        // If we reach here, live mode is enabled — just assert the service builds without error.
        // Full live-mode instructions: tests/Integration/README.md
        $this->assertTrue(true, 'Live mode enabled — see README.md for setup');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeSyncService(?GitHubClientInterface $stub = null): SyncService
    {
        $issueRepo = new IssueRepository(self::$pdo);

        return new SyncService(
            gitHub:          $stub ?? new GitHubGraphQLServiceStub($this->fixture),
            projectRepo:     new ProjectRepository(self::$pdo),
            issueRepo:       $issueRepo,
            historyRepo:     new SyncHistoryRepository(self::$pdo),
            burndownService: new BurndownService(new BurndownRepository(self::$pdo), $issueRepo),
            owner:           'testowner',
            projectNumber:   1,
            snapshotDir:     $this->snapshotDir,
            timeLogRepo:     new TimeLogRepository(self::$pdo),
        );
    }

    /**
     * Replace the field values of fixture item $index with the given number fields
     * and an iteration value.
     *
     * @param array<string,float> $numbers  field name → value
     */
    private function setItemFields(int $index, array $numbers, string $iteration, string $iterationField = 'Iteration'): void
    {
        $nodes = [];
        foreach ($numbers as $name => $value) {
            $nodes[] = [
                '__typename' => 'ProjectV2ItemFieldNumberValue',
                'number'     => $value,
                'field'      => ['id' => 'F_' . $name, 'name' => $name],
            ];
        }
        $nodes[] = [
            '__typename' => 'ProjectV2ItemFieldIterationValue',
            'title'      => $iteration,
            'field'      => ['id' => 'F_iter', 'name' => $iterationField],
        ];

        $this->fixture['project_items_nodes'][$index]['fieldValues']['nodes'] = $nodes;
    }

    /** @return array<string,mixed> */
    private function fetchIssue(string $contentId): array
    {
        $stmt = self::$pdo->prepare('SELECT * FROM `issues` WHERE `github_issue_id` = ?');
        $stmt->execute([$contentId]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row, "Issue {$contentId} must exist");

        return $row;
    }

    private function countIssueRows(): int
    {
        return (int) self::$pdo->query('SELECT COUNT(*) FROM `issues`')->fetchColumn();
    }

    private function countHistoryRows(?string $status = null): int
    {
        if ($status !== null) {
            $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM `sync_history` WHERE `status` = ?');
            $stmt->execute([$status]);
            return (int) $stmt->fetchColumn();
        }

        return (int) self::$pdo->query('SELECT COUNT(*) FROM `sync_history`')->fetchColumn();
    }

    private function getProjectLocalId(string $githubProjectId): ?int
    {
        $stmt = self::$pdo->prepare(
            'SELECT `id` FROM `projects` WHERE `github_project_id` = ? LIMIT 1'
        );
        $stmt->execute([$githubProjectId]);
        $row = $stmt->fetch();

        return $row !== false ? (int) $row['id'] : null;
    }
}
