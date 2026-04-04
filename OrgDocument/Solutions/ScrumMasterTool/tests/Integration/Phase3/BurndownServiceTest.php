<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use App\Models\BurndownPoint;
use App\Repositories\BurndownRepository;
use App\Services\BurndownService;
use PHPUnit\Framework\TestCase;

/**
 * BurndownServiceTest — unit-style tests for BurndownService algorithm logic.
 *
 * Uses PHPUnit mock objects for BurndownRepository so no database is required.
 * Covers:
 *   (a) Ideal curve linear interpolation across a multi-day sprint
 *   (b) Empty-rows edge case — returns empty points array
 *   (c) Mid-sprint carry-forward — missing days inherit the last known actual value
 *   (d) Auto-resolve latest iteration when none supplied
 *   (e) Single-day sprint — ideal stays at total_estimated, no division by zero
 *
 * Run with:
 *   composer test -- --testsuite "Phase3 Unit"
 */
final class BurndownServiceTest extends TestCase
{
    // =========================================================================
    // (a) Ideal curve linear interpolation
    // =========================================================================

    /**
     * Given three snapshot rows spanning three consecutive days and a
     * total_estimated of 30, the service should produce:
     *   day 0  (2026-04-01):  ideal = 30.00
     *   day 1  (2026-04-02):  ideal = 15.00
     *   day 2  (2026-04-03):  ideal =  0.00
     */
    public function testIdealCurveLinearInterpolation(): void
    {
        $rows = [
            [
                'snapshot_date'    => '2026-04-01',
                'total_estimated'  => '30.00',
                'actual_remaining' => '30.00',
                'ideal_remaining'  => '30.00',
                'open_count'       => 5,
                'closed_count'     => 0,
            ],
            [
                'snapshot_date'    => '2026-04-02',
                'total_estimated'  => '30.00',
                'actual_remaining' => '20.00',
                'ideal_remaining'  => '15.00',
                'open_count'       => 4,
                'closed_count'     => 1,
            ],
            [
                'snapshot_date'    => '2026-04-03',
                'total_estimated'  => '30.00',
                'actual_remaining' => '5.00',
                'ideal_remaining'  => '0.00',
                'open_count'       => 1,
                'closed_count'     => 4,
            ],
        ];

        $repo    = $this->makeRepo($rows, 'Sprint 1');
        $service = new BurndownService($repo);
        $result  = $service->getBurndown(1, 'Sprint 1');

        $this->assertSame('Sprint 1', $result['iteration']);
        $this->assertCount(3, $result['points']);

        [$p0, $p1, $p2] = $result['points'];

        $this->assertSame('2026-04-01', $p0->date);
        $this->assertSame(30.0, $p0->ideal);

        $this->assertSame('2026-04-02', $p1->date);
        $this->assertSame(15.0, $p1->ideal);

        $this->assertSame('2026-04-03', $p2->date);
        $this->assertSame(0.0, $p2->ideal);
    }

    // =========================================================================
    // (b) Empty-rows edge case
    // =========================================================================

    /**
     * When BurndownRepository returns no rows, getBurndown() should return an
     * empty points array without throwing.
     */
    public function testEmptyRowsReturnsEmptyPoints(): void
    {
        $repo    = $this->makeRepo([], 'Sprint 1');
        $service = new BurndownService($repo);
        $result  = $service->getBurndown(1, 'Sprint 1');

        $this->assertSame('Sprint 1', $result['iteration']);
        $this->assertSame([], $result['points']);
    }

    /**
     * When no iteration is supplied AND no burndown_daily rows exist (getLatestIteration
     * returns null), getBurndown() returns iteration='' and points=[].
     */
    public function testNoDataAtAllReturnsEmptyResult(): void
    {
        $repo = $this->createMock(BurndownRepository::class);
        $repo->method('getLatestIteration')->willReturn(null);
        $repo->method('getPointsForIteration')->willReturn([]);

        $service = new BurndownService($repo);
        $result  = $service->getBurndown(1);

        $this->assertSame('', $result['iteration']);
        $this->assertSame([], $result['points']);
    }

    // =========================================================================
    // (c) Mid-sprint carry-forward
    // =========================================================================

    /**
     * Snapshots exist on day 0 (actual=30) and day 2 (actual=20).
     * Day 1 is absent from burndown_daily.  The service must fill day 1
     * by carrying forward day 0's actual value (30.00).
     *
     *   date         ideal   actual (expected)
     *   2026-04-01   40.00   30.00      ← row exists
     *   2026-04-02   20.00   30.00      ← no row → carry forward
     *   2026-04-03    0.00   20.00      ← row exists
     */
    public function testMidSprintCarryForward(): void
    {
        // Two rows — day 0 and day 2; day 1 is absent
        $rows = [
            [
                'snapshot_date'    => '2026-04-01',
                'total_estimated'  => '40.00',
                'actual_remaining' => '30.00',
                'ideal_remaining'  => '40.00',
                'open_count'       => 4,
                'closed_count'     => 0,
            ],
            [
                'snapshot_date'    => '2026-04-03',
                'total_estimated'  => '40.00',
                'actual_remaining' => '20.00',
                'ideal_remaining'  => '0.00',
                'open_count'       => 2,
                'closed_count'     => 2,
            ],
        ];

        $repo    = $this->makeRepo($rows, 'Sprint 2');
        $service = new BurndownService($repo);
        $result  = $service->getBurndown(1, 'Sprint 2');

        $this->assertCount(3, $result['points']);

        [$p0, $p1, $p2] = $result['points'];

        // Day 0 — row present
        $this->assertSame('2026-04-01', $p0->date);
        $this->assertSame(30.0, $p0->actual);

        // Day 1 — no row, carry forward from day 0
        $this->assertSame('2026-04-02', $p1->date);
        $this->assertSame(30.0, $p1->actual);

        // Day 2 — row present
        $this->assertSame('2026-04-03', $p2->date);
        $this->assertSame(20.0, $p2->actual);
    }

    // =========================================================================
    // (d) Auto-resolve latest iteration
    // =========================================================================

    /**
     * When $iteration is null the service calls getLatestIteration() to
     * resolve the name, then fetches rows for that iteration.
     */
    public function testAutoResolvesLatestIteration(): void
    {
        $rows = [
            [
                'snapshot_date'    => '2026-04-05',
                'total_estimated'  => '10.00',
                'actual_remaining' => '5.00',
                'ideal_remaining'  => '5.00',
                'open_count'       => 1,
                'closed_count'     => 1,
            ],
        ];

        $repo = $this->createMock(BurndownRepository::class);
        $repo->expects($this->once())
             ->method('getLatestIteration')
             ->with(7)
             ->willReturn('Sprint 3');
        $repo->expects($this->once())
             ->method('getPointsForIteration')
             ->with(7, 'Sprint 3')
             ->willReturn($rows);

        $service = new BurndownService($repo);
        $result  = $service->getBurndown(7);   // no iteration passed

        $this->assertSame('Sprint 3', $result['iteration']);
        $this->assertCount(1, $result['points']);
    }

    // =========================================================================
    // (e) Single-day sprint — no division-by-zero
    // =========================================================================

    /**
     * When all snapshot rows fall on the same date (totalIntervals = 0),
     * the ideal value must equal total_estimated rather than causing a
     * division-by-zero error.
     */
    public function testSingleDaySprintNoDivisionByZero(): void
    {
        $rows = [
            [
                'snapshot_date'    => '2026-04-10',
                'total_estimated'  => '20.00',
                'actual_remaining' => '18.00',
                'ideal_remaining'  => '20.00',
                'open_count'       => 2,
                'closed_count'     => 0,
            ],
        ];

        $repo    = $this->makeRepo($rows, 'Sprint 4');
        $service = new BurndownService($repo);
        $result  = $service->getBurndown(1, 'Sprint 4');

        $this->assertCount(1, $result['points']);
        $this->assertSame(20.0, $result['points'][0]->ideal);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a BurndownRepository stub that returns fixed rows for
     * getPointsForIteration() and the supplied name for getLatestIteration().
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function makeRepo(array $rows, string $latestIteration): BurndownRepository
    {
        $repo = $this->createMock(BurndownRepository::class);
        $repo->method('getLatestIteration')->willReturn($latestIteration);
        $repo->method('getPointsForIteration')->willReturn($rows);

        return $repo;
    }
}
