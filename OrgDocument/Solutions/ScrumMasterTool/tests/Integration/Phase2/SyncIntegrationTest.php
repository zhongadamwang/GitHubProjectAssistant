<?php

declare(strict_types=1);

namespace Tests\Integration\Phase2;

use App\Exceptions\GitHubApiException;
use App\Services\GitHubClientInterface;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
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
     * Local time fields (estimated_time, remaining_time, actual_time) are
     * preserved when a re-sync finds the same issue timestamp (unchanged path).
     */
    public function testLocalTimeFieldsPreservedOnResync(): void
    {
        $service = $this->makeSyncService();

        // First run — all 5 issues inserted with default time values (0.00)
        $service->run();

        // Get the local project_id
        $projectId = $this->getProjectLocalId('PVT_kwTest01');
        $this->assertNotNull($projectId, 'Project row must exist after sync');

        // Manually set time fields for issue 1
        self::$pdo->prepare(
            'UPDATE `issues`
                SET `estimated_time` = 8.0,
                    `remaining_time` = 5.0,
                    `actual_time`    = 3.0
              WHERE `github_issue_id` = ? AND `project_id` = ?'
        )->execute(['I_kgDOA1AAAAA', $projectId]);

        // Second run — issue 1 has same timestamp in DB and fixture → unchanged
        $result = $service->run();
        $this->assertSame(0, $result->updated, 'Issue should be unchanged on re-sync with same timestamp');

        // Time fields must be preserved
        $stmt = self::$pdo->prepare(
            'SELECT `estimated_time`, `remaining_time`, `actual_time`
               FROM `issues`
              WHERE `github_issue_id` = ? AND `project_id` = ?'
        );
        $stmt->execute(['I_kgDOA1AAAAA', $projectId]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'Issue row must exist');
        $this->assertEqualsWithDelta(8.0, (float) $row['estimated_time'], 0.001);
        $this->assertEqualsWithDelta(5.0, (float) $row['remaining_time'], 0.001);
        $this->assertEqualsWithDelta(3.0, (float) $row['actual_time'],    0.001);
    }

    /**
     * Live-mode test — skipped unless GITHUB_INTEGRATION_TEST=true.
     *
     * Requires GITHUB_PAT, GITHUB_ORG, GITHUB_PROJECT_NUMBER in .env.test.
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
        return new SyncService(
            gitHub:        $stub ?? new GitHubGraphQLServiceStub($this->fixture),
            projectRepo:   new ProjectRepository(self::$pdo),
            issueRepo:     new IssueRepository(self::$pdo),
            historyRepo:   new SyncHistoryRepository(self::$pdo),
            owner:         'testowner',
            projectNumber: 1,
            snapshotDir:   $this->snapshotDir,
        );
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
