<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Repositories\IssueRepository;
use App\Repositories\TimeLogRepository;
use App\Services\TimeTrackingService;

/**
 * TimeTrackingServiceTest — unit-level tests for TimeTrackingService (T016).
 */
final class TimeTrackingServiceTest extends TestCase
{
    private static PDO $pdo;

    private TimeTrackingService $service;
    private int                 $projectId;
    private int                 $userId;
    private int                 $issueId;

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
        self::$pdo->exec('TRUNCATE TABLE `time_logs`');
        self::$pdo->exec('TRUNCATE TABLE `issues`');
        self::$pdo->exec('TRUNCATE TABLE `projects`');
        self::$pdo->exec('TRUNCATE TABLE `users`');
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Seed project
        self::$pdo->exec(
            "INSERT INTO `projects` (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`)
             VALUES ('PVT_tt_test', 'owner', '', 1, 'TT Project')"
        );
        $this->projectId = (int) self::$pdo->lastInsertId();

        // Seed user
        $hash = password_hash('Password1!', PASSWORD_BCRYPT, ['cost' => 4]);
        self::$pdo->prepare(
            "INSERT INTO `users` (`email`, `password_hash`, `display_name`, `role`) VALUES (?, ?, ?, 'member')"
        )->execute(['tt@test.local', $hash, 'TT User']);
        $this->userId = (int) self::$pdo->lastInsertId();

        // Seed issue
        self::$pdo->prepare(
            "INSERT INTO `issues`
                 (`project_id`, `github_issue_id`, `title`, `status`, `estimated_time`, `remaining_time`, `actual_time`)
             VALUES (?, 'I_tt_001', 'Test Issue', 'open', 10.0, 10.0, 0.0)"
        )->execute([$this->projectId]);
        $this->issueId = (int) self::$pdo->lastInsertId();

        $this->service = new TimeTrackingService(
            new TimeLogRepository(self::$pdo),
            new IssueRepository(self::$pdo),
            self::$pdo,
        );
    }

    // =========================================================================
    // updateTime — happy path
    // =========================================================================

    public function test_partial_update_only_changes_provided_fields(): void
    {
        $this->service->updateTime($this->issueId, $this->userId, [
            'remaining_time' => 7.0,
        ]);

        $row = self::$pdo->query("SELECT * FROM `issues` WHERE `id` = {$this->issueId}")->fetch();
        $this->assertEqualsWithDelta(10.0, (float) $row['estimated_time'], 0.01, 'estimated should be unchanged');
        $this->assertEqualsWithDelta(7.0,  (float) $row['remaining_time'], 0.01);
        $this->assertEqualsWithDelta(0.0,  (float) $row['actual_time'],    0.01, 'actual should be unchanged');
    }

    public function test_one_log_row_written_per_changed_field(): void
    {
        $this->service->updateTime($this->issueId, $this->userId, [
            'estimated_time' => 8.0,
            'remaining_time' => 6.0,
        ]);

        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM `time_logs`')->fetchColumn();
        $this->assertSame(2, $count);
    }

    public function test_log_row_contains_correct_old_and_new_values(): void
    {
        $this->service->updateTime($this->issueId, $this->userId, [
            'actual_time' => 3.5,
        ]);

        $log = self::$pdo->query(
            "SELECT * FROM `time_logs` WHERE `field_name` = 'actual_time' LIMIT 1"
        )->fetch();

        $this->assertNotFalse($log);
        $this->assertEqualsWithDelta(0.0, (float) $log['old_value'], 0.01);
        $this->assertEqualsWithDelta(3.5, (float) $log['new_value'], 0.01);
    }

    // =========================================================================
    // updateTime — validation failure
    // =========================================================================

    public function test_negative_value_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->updateTime($this->issueId, $this->userId, [
            'estimated_time' => -1.0,
        ]);
    }

    public function test_value_exceeding_max_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->updateTime($this->issueId, $this->userId, [
            'remaining_time' => 10000.0,
        ]);
    }

    public function test_validation_failure_does_not_write_any_logs(): void
    {
        try {
            $this->service->updateTime($this->issueId, $this->userId, [
                'remaining_time' => -5.0,
            ]);
        } catch (\InvalidArgumentException) {
            // expected
        }

        $count = (int) self::$pdo->query('SELECT COUNT(*) FROM `time_logs`')->fetchColumn();
        $this->assertSame(0, $count, 'No log rows should be written on validation failure');
    }

    public function test_transaction_rollback_leaves_issue_unchanged(): void
    {
        // Create a PDO that fails the log insert by violating the FK
        // (user id 999 does not exist → FK violation on time_logs.changed_by)
        try {
            $this->service->updateTime($this->issueId, 999, [
                'estimated_time' => 5.0,
            ]);
        } catch (\Throwable) {
            // expected FK violation
        }

        $row = self::$pdo->query("SELECT `estimated_time` FROM `issues` WHERE `id` = {$this->issueId}")->fetch();
        // Issue should still have original value (10.0) because rollback fired
        $this->assertEqualsWithDelta(10.0, (float) $row['estimated_time'], 0.01);
    }
}
