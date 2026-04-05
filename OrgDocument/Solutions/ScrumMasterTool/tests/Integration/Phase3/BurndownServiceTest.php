<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Models\BurndownPoint;
use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Services\BurndownService;

/**
 * BurndownServiceTest — unit-level tests for BurndownService (T013, T014).
 *
 * Uses the scrum_dashboard_test database directly (no Slim app layer).
 */
final class BurndownServiceTest extends TestCase
{
    private static PDO $pdo;

    private BurndownService    $service;
    private BurndownRepository $burndownRepo;
    private IssueRepository    $issueRepo;

    private int $projectId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $host   = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port   = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user   = $_ENV['DB_USER'] ?? '';
        $pass   = $_ENV['DB_PASS'] ?? '';

        if (empty($dbname) || empty($user)) {
            self::markTestSkipped('DB_NAME and DB_USER must be set to run burndown tests.');
        }

        self::$pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE `burndown_daily`');
        self::$pdo->exec('TRUNCATE TABLE `issues`');
        self::$pdo->exec('TRUNCATE TABLE `projects`');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Seed a project row
        self::$pdo->exec(
            "INSERT INTO `projects` (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`)
             VALUES ('PVT_test_burndown', 'owner', '', 1, 'Test Project')"
        );
        $this->projectId = (int) self::$pdo->lastInsertId();

        $this->burndownRepo = new BurndownRepository(self::$pdo);
        $this->issueRepo    = new IssueRepository(self::$pdo);
        $this->service      = new BurndownService($this->burndownRepo, $this->issueRepo);
    }

    // =========================================================================
    // getBurndown
    // =========================================================================

    public function test_get_burndown_returns_empty_when_no_rows(): void
    {
        $points = $this->service->getBurndown($this->projectId, 'Sprint 1');
        $this->assertSame([], $points);
    }

    public function test_get_burndown_returns_single_point(): void
    {
        $this->burndownRepo->upsertDailySnapshot($this->projectId, 'Sprint 1', [
            'total_estimated'  => 10.0,
            'ideal_remaining'  => 10.0,
            'actual_remaining' => 8.0,
            'open_count'       => 2,
            'closed_count'     => 1,
        ]);

        $points = $this->service->getBurndown($this->projectId, 'Sprint 1');

        $this->assertCount(1, $points);
        $this->assertInstanceOf(BurndownPoint::class, $points[0]);
        // Single-point ideal equals total_estimated
        $this->assertEqualsWithDelta(10.0, $points[0]->ideal,  0.01);
        $this->assertEqualsWithDelta(8.0,  $points[0]->actual, 0.01);
    }

    public function test_ideal_curve_linear_interpolation(): void
    {
        // Insert 5 days of snapshots
        $dates = ['2026-04-01', '2026-04-02', '2026-04-03', '2026-04-04', '2026-04-05'];
        foreach ($dates as $date) {
            self::$pdo->prepare(
                "INSERT INTO `burndown_daily`
                     (`project_id`, `iteration`, `snapshot_date`, `total_estimated`, `ideal_remaining`, `actual_remaining`, `open_count`, `closed_count`)
                 VALUES (?, 'Sprint 1', ?, 20.0, 0.0, 10.0, 3, 1)"
            )->execute([$this->projectId, $date]);
        }

        $points = $this->service->getBurndown($this->projectId, 'Sprint 1');

        $this->assertCount(5, $points);
        // First point ideal = total_estimated = 20
        $this->assertEqualsWithDelta(20.0, $points[0]->ideal, 0.01);
        // Last point ideal = 0
        $this->assertEqualsWithDelta(0.0, $points[4]->ideal, 0.01);
        // Middle point ideal ≈ 10
        $this->assertEqualsWithDelta(10.0, $points[2]->ideal, 0.01);
    }

    public function test_all_zero_estimates_keeps_ideal_at_zero(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `burndown_daily`
                 (`project_id`, `iteration`, `snapshot_date`, `total_estimated`, `ideal_remaining`, `actual_remaining`, `open_count`, `closed_count`)
             VALUES (?, 'Sprint 1', '2026-04-01', 0.0, 0.0, 0.0, 0, 0)"
        )->execute([$this->projectId]);

        $points = $this->service->getBurndown($this->projectId, 'Sprint 1');

        $this->assertCount(1, $points);
        $this->assertEqualsWithDelta(0.0, $points[0]->ideal, 0.01);
    }

    public function test_get_burndown_returns_empty_for_nonexistent_iteration(): void
    {
        $points = $this->service->getBurndown($this->projectId, 'NonExistent Sprint');
        $this->assertSame([], $points);
    }

    // =========================================================================
    // captureDaily
    // =========================================================================

    public function test_capture_daily_writes_row_per_iteration(): void
    {
        // Seed two issues in different iterations
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `iteration`, `estimated_time`, `remaining_time`)
             VALUES (?, 'I_001', 'Issue 1', 'open', 'Sprint 1', 8.0, 5.0),
                    (?, 'I_002', 'Issue 2', 'open', 'Sprint 2', 4.0, 4.0)"
        )->execute([$this->projectId, $this->projectId]);

        $this->service->captureDaily($this->projectId);

        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM `burndown_daily`')->fetchColumn();
        $this->assertSame(2, $count);
    }

    public function test_capture_daily_is_idempotent(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `iteration`, `estimated_time`, `remaining_time`)
             VALUES (?, 'I_001', 'Issue 1', 'open', 'Sprint 1', 8.0, 5.0)"
        )->execute([$this->projectId]);

        $this->service->captureDaily($this->projectId);
        $this->service->captureDaily($this->projectId);  // second call same day

        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM `burndown_daily`')->fetchColumn();
        $this->assertSame(1, $count, 'Second captureDaily on same day must not add a duplicate row');
    }

    public function test_capture_daily_sets_correct_aggregates(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `iteration`, `estimated_time`, `remaining_time`)
             VALUES (?, 'I_001', 'Open Issue',   'open',   'Sprint 1', 8.0, 5.0),
                    (?, 'I_002', 'Closed Issue', 'closed', 'Sprint 1', 4.0, 0.0)"
        )->execute([$this->projectId, $this->projectId]);

        $this->service->captureDaily($this->projectId);

        $row = self::$pdo->query(
            "SELECT * FROM `burndown_daily` WHERE `iteration` = 'Sprint 1' LIMIT 1"
        )->fetch();

        $this->assertNotFalse($row);
        $this->assertEqualsWithDelta(12.0, (float) $row['total_estimated'],  0.01);
        $this->assertEqualsWithDelta(5.0,  (float) $row['actual_remaining'], 0.01);
        $this->assertSame(1, (int) $row['open_count']);
        $this->assertSame(1, (int) $row['closed_count']);
    }
}
