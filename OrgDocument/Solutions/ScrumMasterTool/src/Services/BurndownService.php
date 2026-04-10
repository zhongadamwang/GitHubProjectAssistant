<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BurndownPoint;
use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;

/**
 * BurndownService — calculates ideal and actual burndown curves for a sprint.
 *
 * Ideal curve: linear interpolation from total_estimated on the first snapshot
 * date down to 0 on the last snapshot date.  The date range is derived
 * entirely from what exists in burndown_daily — no sprint length is hard-coded.
 *
 * Actual curve: sourced from burndown_daily.actual_remaining per snapshot_date.
 * Calendar days between snapshots are filled by carrying the last known value
 * forward, so the chart always covers a continuous date range.
 */
final class BurndownService
{
    public function __construct(
        private readonly BurndownRepository $repo,
        private readonly IssueRepository    $issueRepo,
    ) {
    }

    /**
     * Return burndown data for a project iteration.
     *
     * When $iteration is null the most recent iteration name is resolved
     * automatically from burndown_daily.  If no snapshot rows exist at all,
     * returns ['iteration' => '', 'points' => []].
     *
     * Returned array shape:
     * [
     *   'iteration' => string,
     *   'points'    => BurndownPoint[],   // one per calendar day, ordered ASC
     * ]
     *
     * @return array{iteration: string, points: list<BurndownPoint>}
     */
    public function getBurndown(int $projectId, ?string $iteration = null): array
    {
        // Resolve iteration if not supplied
        if ($iteration === null) {
            $iteration = $this->repo->getLatestIteration($projectId);
        }

        if ($iteration === null) {
            return ['iteration' => '', 'points' => []];
        }

        $rows = $this->repo->getPointsForIteration($projectId, $iteration);

        if (empty($rows)) {
            return ['iteration' => $iteration, 'points' => []];
        }

        // Use the first row's total_estimated as the sprint's baseline
        $totalEstimated = (float) $rows[0]['total_estimated'];

        // Build date→actual_remaining lookup from rows with real data
        $actualMap = [];
        foreach ($rows as $row) {
            $actualMap[$row['snapshot_date']] = (float) $row['actual_remaining'];
        }

        $firstDate = $rows[0]['snapshot_date'];
        $lastDate  = $rows[count($rows) - 1]['snapshot_date'];

        $start = new \DateTimeImmutable($firstDate);
        $end   = new \DateTimeImmutable($lastDate);

        // Number of calendar intervals (0 when start === end → single-day sprint)
        $totalIntervals = (int) $start->diff($end)->days;

        $points     = [];
        $current    = $start;
        $dayIndex   = 0;
        $lastActual = $totalEstimated; // carry-forward seed: assume full load before data

        while ($current <= $end) {
            $date = $current->format('Y-m-d');

            // Ideal: totalEstimated on day 0, 0 on last day (linear)
            if ($totalIntervals === 0) {
                $ideal = $totalEstimated;
            } else {
                $ideal = round($totalEstimated * (1.0 - $dayIndex / $totalIntervals), 2);
            }

            // Actual: use the snapshot value when present; carry forward otherwise
            if (array_key_exists($date, $actualMap)) {
                $lastActual = $actualMap[$date];
            }

            $points[] = new BurndownPoint(
                date:   $date,
                ideal:  $ideal,
                actual: round($lastActual, 2),
            );

            $current = $current->modify('+1 day');
            $dayIndex++;
        }

        return ['iteration' => $iteration, 'points' => $points];
    }

    /**
     * Capture today's burndown snapshot for all iterations of a project.
     *
     * Aggregates SUM(estimated_time), SUM(remaining_time), and open/closed
     * counts from the issues table grouped by iteration, then upserts one
     * row per iteration into burndown_daily for today's date.
     *
     * Idempotent: calling twice on the same day overwrites via ON DUPLICATE KEY.
     * Safe to call when the project has no issues — loop body never executes.
     *
     * @param int $projectId  Local projects.id
     */
    public function captureDaily(int $projectId): void
    {
        $rows = $this->issueRepo->aggregateTimeByIteration($projectId);
        $today = (new \DateTimeImmutable('today', new \DateTimeZone('UTC')))->format('Y-m-d');

        foreach ($rows as $row) {
            $remaining = (float) $row['total_remaining'];

            $this->repo->upsertDailySnapshot($projectId, [
                'iteration'        => $row['iteration'],
                'snapshot_date'    => $today,
                'total_estimated'  => (float) $row['total_estimated'],
                'ideal_remaining'  => $remaining,
                'actual_remaining' => $remaining,
                'open_count'       => (int) $row['open_count'],
                'closed_count'     => (int) $row['closed_count'],
            ]);
        }
    }
}
