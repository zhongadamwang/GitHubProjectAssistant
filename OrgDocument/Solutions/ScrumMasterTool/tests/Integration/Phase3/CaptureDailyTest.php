<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;
use App\Services\BurndownService;
use PHPUnit\Framework\TestCase;

/**
 * CaptureDailyTest — unit-style tests for BurndownService::captureDaily().
 *
 * Uses PHPUnit mock objects for both BurndownRepository and IssueRepository
 * so no database is required.
 *
 * Covers:
 *   (a) captureDaily() calls upsertDailySnapshot() once per iteration with
 *       correctly mapped values
 *   (b) Idempotency — calling captureDaily() twice triggers upsertDailySnapshot()
 *       twice (the ON DUPLICATE KEY UPDATE on the DB side makes it idempotent,
 *       but the service must always call through without guarding against re-calls)
 *   (c) Multi-iteration project — one upsert call per distinct iteration
 *   (d) Empty project (no issues) — upsertDailySnapshot() is never called
 *   (e) snapshot_date uses today's UTC date
 *
 * Run with:
 *   composer test -- --testsuite "Phase3 Unit"
 */
final class CaptureDailyTest extends TestCase
{
    // =========================================================================
    // (a) Single iteration — correct values mapped to upsertDailySnapshot
    // =========================================================================

    /**
     * Given one iteration row from IssueRepository::aggregateTimeByIteration(),
     * captureDaily() must call BurndownRepository::upsertDailySnapshot() once
     * with the correctly mapped data array.
     *
     * total_estimated  → data['total_estimated']
     * total_remaining  → data['ideal_remaining'] AND data['actual_remaining']
     * open_count       → data['open_count']
     * closed_count     → data['closed_count']
     * snapshot_date    → today's UTC date (Y-m-d)
     */
    public function testSingleIterationCallsUpsertWithCorrectValues(): void
    {
        $aggregateRow = [
            'iteration'       => 'Sprint 1',
            'total_estimated' => '30.00',
            'total_remaining' => '20.00',
            'open_count'      => '3',
            'closed_count'    => '2',
        ];

        $today = (new \DateTimeImmutable('today', new \DateTimeZone('UTC')))->format('Y-m-d');

        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->expects($this->once())
                  ->method('aggregateTimeByIteration')
                  ->with(42)
                  ->willReturn([$aggregateRow]);

        $burndownRepo = $this->createMock(BurndownRepository::class);
        $burndownRepo->expects($this->once())
                     ->method('upsertDailySnapshot')
                     ->with(
                         42,
                         $this->callback(static function (array $data) use ($today): bool {
                             return $data['iteration']        === 'Sprint 1'
                                 && $data['snapshot_date']    === $today
                                 && $data['total_estimated']  === 30.0
                                 && $data['ideal_remaining']  === 20.0
                                 && $data['actual_remaining'] === 20.0
                                 && $data['open_count']       === 3
                                 && $data['closed_count']     === 2;
                         }),
                     );

        $service = new BurndownService($burndownRepo, $issueRepo);
        $service->captureDaily(42);
    }

    // =========================================================================
    // (b) Idempotency — two calls each go through to upsertDailySnapshot
    // =========================================================================

    /**
     * The service must not short-circuit on a second call within the same day.
     * The database's ON DUPLICATE KEY UPDATE handles idempotency; the service
     * must always delegate to the repository.
     */
    public function testCallingTwiceCallsUpsertTwice(): void
    {
        $aggregateRow = [
            'iteration'       => 'Sprint 1',
            'total_estimated' => '10.00',
            'total_remaining' => '5.00',
            'open_count'      => '1',
            'closed_count'    => '1',
        ];

        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateTimeByIteration')->willReturn([$aggregateRow]);

        $burndownRepo = $this->createMock(BurndownRepository::class);
        $burndownRepo->expects($this->exactly(2))
                     ->method('upsertDailySnapshot');

        $service = new BurndownService($burndownRepo, $issueRepo);
        $service->captureDaily(1);
        $service->captureDaily(1);
    }

    // =========================================================================
    // (c) Multi-iteration project — one upsert per iteration
    // =========================================================================

    /**
     * When aggregateTimeByIteration returns two distinct iterations,
     * upsertDailySnapshot must be called once per iteration (twice total).
     */
    public function testMultipleIterationsEachGetUpserted(): void
    {
        $rows = [
            [
                'iteration'       => 'Sprint 1',
                'total_estimated' => '40.00',
                'total_remaining' => '10.00',
                'open_count'      => '1',
                'closed_count'    => '4',
            ],
            [
                'iteration'       => 'Sprint 2',
                'total_estimated' => '20.00',
                'total_remaining' => '20.00',
                'open_count'      => '2',
                'closed_count'    => '0',
            ],
        ];

        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateTimeByIteration')->willReturn($rows);

        $calledIterations = [];

        $burndownRepo = $this->createMock(BurndownRepository::class);
        $burndownRepo->expects($this->exactly(2))
                     ->method('upsertDailySnapshot')
                     ->willReturnCallback(
                         static function (int $projectId, array $data) use (&$calledIterations): void {
                             $calledIterations[] = $data['iteration'];
                         },
                     );

        $service = new BurndownService($burndownRepo, $issueRepo);
        $service->captureDaily(5);

        $this->assertSame(['Sprint 1', 'Sprint 2'], $calledIterations);
    }

    // =========================================================================
    // (d) Empty project — upsertDailySnapshot never called
    // =========================================================================

    /**
     * When the project has no issues (or no issues with an iteration set),
     * aggregateTimeByIteration returns [] and captureDaily must not call
     * upsertDailySnapshot at all.
     */
    public function testEmptyProjectDoesNotCallUpsert(): void
    {
        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateTimeByIteration')->willReturn([]);

        $burndownRepo = $this->createMock(BurndownRepository::class);
        $burndownRepo->expects($this->never())->method('upsertDailySnapshot');

        $service = new BurndownService($burndownRepo, $issueRepo);
        $service->captureDaily(99);
    }

    // =========================================================================
    // (e) snapshot_date is today's UTC date
    // =========================================================================

    /**
     * The snapshot_date passed to upsertDailySnapshot must be today's UTC date
     * in Y-m-d format, not a hard-coded or server-local-timezone value.
     */
    public function testSnapshotDateIsUtcToday(): void
    {
        $expectedDate = (new \DateTimeImmutable('today', new \DateTimeZone('UTC')))->format('Y-m-d');

        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateTimeByIteration')->willReturn([
            [
                'iteration'       => 'Sprint X',
                'total_estimated' => '0.00',
                'total_remaining' => '0.00',
                'open_count'      => '0',
                'closed_count'    => '0',
            ],
        ]);

        $capturedDate = null;
        $burndownRepo = $this->createMock(BurndownRepository::class);
        $burndownRepo->method('upsertDailySnapshot')
                     ->willReturnCallback(
                         static function (int $projectId, array $data) use (&$capturedDate): void {
                             $capturedDate = $data['snapshot_date'];
                         },
                     );

        $service = new BurndownService($burndownRepo, $issueRepo);
        $service->captureDaily(1);

        $this->assertSame($expectedDate, $capturedDate);
    }
}
