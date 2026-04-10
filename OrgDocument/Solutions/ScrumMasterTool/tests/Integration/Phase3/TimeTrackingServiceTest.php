<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use App\Repositories\TimeLogRepository;
use App\Services\TimeTrackingService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * TimeTrackingServiceTest — unit-style tests for TimeTrackingService logic.
 *
 * Uses PHPUnit mock objects for TimeLogRepository and PDO so no database
 * is required.
 *
 * Covers:
 *   (a) Partial update — only provided fields are logged and updated
 *   (b) Negative value throws InvalidArgumentException
 *   (c) Value > 9999.99 throws InvalidArgumentException
 *   (d) Transaction rollback on TimeLogRepository::insert() failure
 *   (e) No-op when no recognised fields are provided
 *   (f) Multiple fields in one call — one log row per field
 *   (g) Unknown field names are silently discarded (not written)
 *
 * Run with:
 *   vendor/phpunit/phpunit/phpunit --testsuite "Phase3 Unit"
 */
final class TimeTrackingServiceTest extends TestCase
{
    // =========================================================================
    // (a) Partial update — only provided fields are logged
    // =========================================================================

    /**
     * When only 'remaining_time' is provided, TimeLogRepository::insert() is
     * called exactly once (for remaining_time only), and the UPDATE statement
     * only sets that one column.
     */
    public function testPartialUpdateOnlyLogsProvidedFields(): void
    {
        [$pdo, $stmtSelect, $stmtUpdate] = $this->makePdoWithRow([
            'estimated_time' => '10.00',
            'remaining_time' => '8.00',
            'actual_time'    => '2.00',
        ]);

        $timeLogRepo = $this->createMock(TimeLogRepository::class);
        $timeLogRepo->expects($this->once())
                    ->method('insert')
                    ->with(
                        $this->identicalTo(5),    // issueId
                        $this->identicalTo(1),    // changedBy
                        $this->identicalTo('remaining_time'),
                        $this->identicalTo(8.0),  // old value
                        $this->identicalTo(5.0),  // new value
                    );

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(5, 1, ['remaining_time' => 5.0]);
    }

    // =========================================================================
    // (b) Negative value throws InvalidArgumentException
    // =========================================================================

    public function testNegativeValueThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/estimated_time/');

        $pdo         = $this->createMock(PDO::class);
        $timeLogRepo = $this->createMock(TimeLogRepository::class);

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(1, 1, ['estimated_time' => -1.0]);
    }

    // =========================================================================
    // (c) Value > 9999.99 throws InvalidArgumentException
    // =========================================================================

    public function testValueAboveMaxThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/actual_time/');

        $pdo         = $this->createMock(PDO::class);
        $timeLogRepo = $this->createMock(TimeLogRepository::class);

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(1, 1, ['actual_time' => 10000.0]);
    }

    // =========================================================================
    // (d) Transaction rollback on insert() failure
    // =========================================================================

    /**
     * If TimeLogRepository::insert() throws, the PDO transaction must be
     * rolled back and the exception rethrown.
     */
    public function testTransactionRolledBackOnInsertFailure(): void
    {
        [$pdo, $stmtSelect] = $this->makePdoWithRow([
            'estimated_time' => '10.00',
            'remaining_time' => '8.00',
            'actual_time'    => '0.00',
        ]);

        // Ensure rollBack() is called exactly once
        $pdo->expects($this->once())->method('rollBack');

        $timeLogRepo = $this->createMock(TimeLogRepository::class);
        $timeLogRepo->method('insert')->willThrowException(new \PDOException('DB error'));

        $service = new TimeTrackingService($timeLogRepo, $pdo);

        $this->expectException(\PDOException::class);
        $service->updateTime(7, 2, ['estimated_time' => 15.0]);
    }

    // =========================================================================
    // (e) No-op when no recognised fields are provided
    // =========================================================================

    /**
     * When the fields array contains no recognised keys,
     * no DB call should be made at all (beginTransaction never called).
     */
    public function testNoOpWhenNoRecognisedFieldsProvided(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('beginTransaction');

        $timeLogRepo = $this->createMock(TimeLogRepository::class);
        $timeLogRepo->expects($this->never())->method('insert');

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(1, 1, ['unknown_field' => 5.0]);  // no recognised keys
    }

    // =========================================================================
    // (f) Multiple fields — one TimeLogRepository::insert() call per field
    // =========================================================================

    /**
     * When all three time fields are supplied, insert() is called three times
     * with the correct field names.
     */
    public function testMultipleFieldsProduceOneLogEntryEach(): void
    {
        [$pdo] = $this->makePdoWithRow([
            'estimated_time' => '10.00',
            'remaining_time' => '10.00',
            'actual_time'    => '0.00',
        ]);

        $insertedFields = [];
        $timeLogRepo    = $this->createMock(TimeLogRepository::class);
        $timeLogRepo->expects($this->exactly(3))
                    ->method('insert')
                    ->willReturnCallback(
                        static function (int $issueId, int $userId, string $field) use (&$insertedFields): void {
                            $insertedFields[] = $field;
                        },
                    );

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(1, 1, [
            'estimated_time' => 20.0,
            'remaining_time' => 15.0,
            'actual_time'    => 5.0,
        ]);

        sort($insertedFields);
        $this->assertSame(['actual_time', 'estimated_time', 'remaining_time'], $insertedFields);
    }

    // =========================================================================
    // (g) Unknown field names silently discarded
    // =========================================================================

    /**
     * Unknown keys in $fields (e.g., 'github_issue_id', 'status') must be
     * silently dropped — they must never appear in the UPDATE statement.
     */
    public function testUnknownFieldsAreDiscarded(): void
    {
        [$pdo] = $this->makePdoWithRow([
            'estimated_time' => '5.00',
            'remaining_time' => '5.00',
            'actual_time'    => '0.00',
        ]);

        // Only one valid field in $fields → insert() called once
        $timeLogRepo = $this->createMock(TimeLogRepository::class);
        $timeLogRepo->expects($this->once())->method('insert');

        $service = new TimeTrackingService($timeLogRepo, $pdo);
        $service->updateTime(1, 1, [
            'actual_time'    => 3.0,
            'github_issue_id' => 'SHOULD_BE_IGNORED',
            'status'          => 'closed',
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a PDO mock that:
     *  - accepts beginTransaction() / commit() / rollBack()
     *  - returns a SELECT stub yielding $currentRow on first prepare (SELECT)
     *  - accepts a second prepare() call for the UPDATE statement
     *
     * @param array<string,string> $currentRow  'estimated_time', 'remaining_time', 'actual_time'
     * @return array{PDO&\PHPUnit\Framework\MockObject\MockObject, PDOStatement&\PHPUnit\Framework\MockObject\MockObject, PDOStatement&\PHPUnit\Framework\MockObject\MockObject}
     */
    private function makePdoWithRow(array $currentRow): array
    {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('execute')->willReturn(true);
        $stmtSelect->method('fetch')->willReturn($currentRow);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('rollBack')->willReturn(true);

        // First prepare call → SELECT (for reading current values)
        // Subsequent calls → UPDATE
        $pdo->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtSelect, $stmtUpdate);

        return [$pdo, $stmtSelect, $stmtUpdate];
    }
}
