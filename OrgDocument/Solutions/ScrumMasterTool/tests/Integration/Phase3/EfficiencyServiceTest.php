<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use App\Repositories\IssueRepository;
use App\Services\EfficiencyService;
use PHPUnit\Framework\TestCase;

/**
 * EfficiencyServiceTest — unit-style tests for EfficiencyService logic.
 *
 * Uses PHPUnit mock objects for IssueRepository so no database is required.
 *
 * Covers:
 *   (a) Correct ratio calculated from sum of estimated / actual for closed issues
 *   (b) ratio is null when estimated = 0 (even if actual > 0)
 *   (c) ratio is null when actual = 0 (no work logged)
 *   (d) Iteration filter is forwarded to aggregateEfficiencyByMember()
 *   (e) getMemberTrend() filters rows by member name and preserves iteration order
 *   (f) Empty result — no closed issues — returns empty array
 *   (g) Multiple members — each gets its own record with correctly calculated ratio
 *
 * Run with:
 *   vendor/phpunit/phpunit/phpunit --testsuite "Phase3 Unit"
 */
final class EfficiencyServiceTest extends TestCase
{
    // =========================================================================
    // (a) Correct ratio calculation
    // =========================================================================

    /**
     * Given estimated=20, actual=25 the ratio should be 25/20 = 1.25.
     */
    public function testRatioCalculatedCorrectly(): void
    {
        $rows = [[
            'member'       => 'alice',
            'estimated'    => '20.00',
            'actual'       => '25.00',
            'issues_count' => '4',
        ]];

        $issueRepo = $this->makeEfficiencyRepo($rows);
        $service   = new EfficiencyService($issueRepo);

        $result = $service->getMemberEfficiency(1);

        $this->assertCount(1, $result);
        $this->assertSame('alice',  $result[0]['member']);
        $this->assertSame(20.0,     $result[0]['estimated']);
        $this->assertSame(25.0,     $result[0]['actual']);
        $this->assertSame(1.25,     $result[0]['ratio']);
        $this->assertSame(4,        $result[0]['issues_count']);
    }

    // =========================================================================
    // (b) ratio is null when estimated = 0
    // =========================================================================

    /**
     * When a member has all-null estimated_time (COALESCE → 0.00) the ratio
     * must be null rather than triggering a division-by-zero.
     */
    public function testRatioIsNullWhenEstimatedIsZero(): void
    {
        $rows = [[
            'member'       => 'bob',
            'estimated'    => '0.00',
            'actual'       => '10.00',
            'issues_count' => '2',
        ]];

        $issueRepo = $this->makeEfficiencyRepo($rows);
        $service   = new EfficiencyService($issueRepo);

        $result = $service->getMemberEfficiency(1);

        $this->assertNull($result[0]['ratio']);
    }

    // =========================================================================
    // (c) ratio is null when actual = 0
    // =========================================================================

    /**
     * When actual_time is 0 (no work logged yet), the ratio should be null
     * because the member hasn't recorded any time — a ratio of 0 would be
     * misleading.
     */
    public function testRatioIsNullWhenActualIsZero(): void
    {
        $rows = [[
            'member'       => 'carol',
            'estimated'    => '15.00',
            'actual'       => '0.00',
            'issues_count' => '3',
        ]];

        $issueRepo = $this->makeEfficiencyRepo($rows);
        $service   = new EfficiencyService($issueRepo);

        $result = $service->getMemberEfficiency(1);

        $this->assertNull($result[0]['ratio']);
    }

    // =========================================================================
    // (d) Iteration filter forwarded to repository
    // =========================================================================

    /**
     * When getMemberEfficiency() is called with an iteration string, it must
     * forward that value directly to IssueRepository::aggregateEfficiencyByMember().
     */
    public function testIterationFilterIsForwarded(): void
    {
        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->expects($this->once())
                  ->method('aggregateEfficiencyByMember')
                  ->with(5, 'Sprint 2')
                  ->willReturn([]);

        $service = new EfficiencyService($issueRepo);
        $result  = $service->getMemberEfficiency(5, 'Sprint 2');

        $this->assertSame([], $result);
    }

    /**
     * When called with null iteration, the repository receives null.
     */
    public function testNullIterationForwarded(): void
    {
        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->expects($this->once())
                  ->method('aggregateEfficiencyByMember')
                  ->with(3, null)
                  ->willReturn([]);

        $service = new EfficiencyService($issueRepo);
        $service->getMemberEfficiency(3, null);
    }

    // =========================================================================
    // (e) getMemberTrend() filters by member and preserves order
    // =========================================================================

    /**
     * aggregateEfficiencyByMemberAndIteration returns rows for two members
     * interleaved.  getMemberTrend('alice') must return only alice's rows
     * in the order the repository returns them (alphabetical by iteration).
     */
    public function testMemberTrendFiltersCorrectly(): void
    {
        $allRows = [
            ['member' => 'alice', 'iteration' => 'Sprint 1', 'estimated' => '10.00', 'actual' => '12.00', 'issues_count' => '2'],
            ['member' => 'bob',   'iteration' => 'Sprint 1', 'estimated' => '8.00',  'actual' => '6.00',  'issues_count' => '1'],
            ['member' => 'alice', 'iteration' => 'Sprint 2', 'estimated' => '20.00', 'actual' => '18.00', 'issues_count' => '3'],
        ];

        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateEfficiencyByMemberAndIteration')->willReturn($allRows);

        $service = new EfficiencyService($issueRepo);
        $trend   = $service->getMemberTrend(1, 'alice');

        $this->assertCount(2, $trend);

        $this->assertSame('Sprint 1', $trend[0]['iteration']);
        $this->assertSame(10.0,       $trend[0]['estimated']);
        $this->assertSame(12.0,       $trend[0]['actual']);
        $this->assertSame(1.2,        $trend[0]['ratio']);   // 12/10
        $this->assertSame(2,          $trend[0]['issues_count']);

        $this->assertSame('Sprint 2', $trend[1]['iteration']);
        $this->assertSame(0.9,        $trend[1]['ratio']);   // 18/20
    }

    /**
     * If the member has no rows at all, getMemberTrend() returns [].
     */
    public function testMemberTrendEmptyForUnknownMember(): void
    {
        $issueRepo = $this->createMock(IssueRepository::class);
        $issueRepo->method('aggregateEfficiencyByMemberAndIteration')->willReturn([]);

        $service = new EfficiencyService($issueRepo);
        $trend   = $service->getMemberTrend(1, 'nobody');

        $this->assertSame([], $trend);
    }

    // =========================================================================
    // (f) Empty result — no closed issues
    // =========================================================================

    public function testEmptyResultWhenNoClosedIssues(): void
    {
        $issueRepo = $this->makeEfficiencyRepo([]);
        $service   = new EfficiencyService($issueRepo);

        $result = $service->getMemberEfficiency(42);

        $this->assertSame([], $result);
    }

    // =========================================================================
    // (g) Multiple members with correct independent ratios
    // =========================================================================

    public function testMultipleMembersGetIndependentRatios(): void
    {
        $rows = [
            ['member' => 'alice', 'estimated' => '10.00', 'actual' => '10.00', 'issues_count' => '1'],
            ['member' => 'bob',   'estimated' => '10.00', 'actual' => '5.00',  'issues_count' => '1'],
        ];

        $issueRepo = $this->makeEfficiencyRepo($rows);
        $service   = new EfficiencyService($issueRepo);

        $result = $service->getMemberEfficiency(1);

        $this->assertCount(2, $result);

        $byMember = array_column($result, null, 'member');

        $this->assertSame(1.0,  $byMember['alice']['ratio']);  // perfect
        $this->assertSame(0.5,  $byMember['bob']['ratio']);    // overestimated
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build an IssueRepository stub whose aggregateEfficiencyByMember()
     * returns fixed rows regardless of parameters.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    private function makeEfficiencyRepo(array $rows): IssueRepository
    {
        $repo = $this->createMock(IssueRepository::class);
        $repo->method('aggregateEfficiencyByMember')->willReturn($rows);

        return $repo;
    }
}
