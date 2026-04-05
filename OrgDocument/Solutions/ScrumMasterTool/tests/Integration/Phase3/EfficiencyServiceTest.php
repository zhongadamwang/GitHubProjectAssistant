<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\IssueRepository;
use App\Services\EfficiencyService;

/**
 * EfficiencyServiceTest — unit-level tests for EfficiencyService (T015).
 */
final class EfficiencyServiceTest extends TestCase
{
    private static PDO $pdo;

    private EfficiencyService $service;
    private int               $projectId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $host   = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port   = $_ENV['DB_PORT'] ?? '3306';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user   = $_ENV['DB_USER'] ?? '';
        $pass   = $_ENV['DB_PASS'] ?? '';

        if (empty($dbname) || empty($user)) {
            self::markTestSkipped('DB credentials required.');
        }

        self::$pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        self::$pdo->exec('TRUNCATE TABLE `issues`');
        self::$pdo->exec('TRUNCATE TABLE `projects`');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        self::$pdo->exec(
            "INSERT INTO `projects` (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`)
             VALUES ('PVT_eff_test', 'owner', '', 1, 'Eff Project')"
        );
        $this->projectId = (int) self::$pdo->lastInsertId();

        $this->service = new EfficiencyService(new IssueRepository(self::$pdo));
    }

    // =========================================================================
    // getMemberEfficiency
    // =========================================================================

    public function test_returns_empty_when_no_closed_issues(): void
    {
        // Only open issues — should produce no records
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `assignee`, `estimated_time`, `actual_time`, `iteration`)
             VALUES (?, 'I_001', 'Open', 'open', 'alice', 8.0, 3.0, 'Sprint 1')"
        )->execute([$this->projectId]);

        $result = $this->service->getMemberEfficiency($this->projectId);
        $this->assertSame([], $result);
    }

    public function test_ratio_is_null_when_estimated_is_zero(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `assignee`, `estimated_time`, `actual_time`)
             VALUES (?, 'I_001', 'Issue', 'closed', 'alice', 0.0, 5.0)"
        )->execute([$this->projectId]);

        $result = $this->service->getMemberEfficiency($this->projectId);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]['ratio']);
    }

    public function test_ratio_calculated_correctly(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `assignee`, `estimated_time`, `actual_time`)
             VALUES (?, 'I_001', 'Issue', 'closed', 'bob', 10.0, 8.0)"
        )->execute([$this->projectId]);

        $result = $this->service->getMemberEfficiency($this->projectId);

        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(0.8, $result[0]['ratio'], 0.0001);
        $this->assertSame('bob', $result[0]['member']);
        $this->assertEqualsWithDelta(10.0, $result[0]['estimated'], 0.01);
        $this->assertEqualsWithDelta(8.0,  $result[0]['actual'],    0.01);
        $this->assertSame(1, $result[0]['issues_count']);
    }

    public function test_iteration_filter_scopes_results(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `assignee`, `estimated_time`, `actual_time`, `iteration`)
             VALUES (?, 'I_001', 'Sprint 1', 'closed', 'alice', 10.0, 8.0, 'Sprint 1'),
                    (?, 'I_002', 'Sprint 2', 'closed', 'alice',  6.0, 6.0, 'Sprint 2')"
        )->execute([$this->projectId, $this->projectId]);

        $sprint1 = $this->service->getMemberEfficiency($this->projectId, 'Sprint 1');
        $this->assertCount(1, $sprint1);
        $this->assertEqualsWithDelta(10.0, $sprint1[0]['estimated'], 0.01);

        $sprint2 = $this->service->getMemberEfficiency($this->projectId, 'Sprint 2');
        $this->assertCount(1, $sprint2);
        $this->assertEqualsWithDelta(6.0, $sprint2[0]['estimated'], 0.01);
    }

    // =========================================================================
    // getMemberTrend
    // =========================================================================

    public function test_trend_ordered_by_iteration_asc(): void
    {
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `assignee`, `estimated_time`, `actual_time`, `iteration`)
             VALUES (?, 'I_001', 'S2', 'closed', 'alice', 6.0, 6.0, 'Sprint 2'),
                    (?, 'I_002', 'S1', 'closed', 'alice', 8.0, 4.0, 'Sprint 1')"
        )->execute([$this->projectId, $this->projectId]);

        $trend = $this->service->getMemberTrend($this->projectId, 'alice');

        $this->assertCount(2, $trend);
        $this->assertSame('Sprint 1', $trend[0]['iteration']);
        $this->assertSame('Sprint 2', $trend[1]['iteration']);
    }
}
