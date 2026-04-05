<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BurndownPoint;
use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;

/**
 * BurndownService — calculates ideal and actual burndown curves.
 *
 * getBurndown():   reads burndown_daily -> produces BurndownPoint[]
 * captureDaily():  aggregates issues -> upserts into burndown_daily
 *                  Called from SyncService after each successful sync (T014).
 */
final class BurndownService
{
    public function __construct(
        private readonly BurndownRepository $burndownRepo,
        private readonly IssueRepository    $issueRepo,
    ) {
    }

    /**
     * Return burndown data points for a project iteration.
     *
     * Ideal curve: linear interpolation from total_estimated on the first
     * snapshot_date down to 0 on the last snapshot_date, over all captured days.
     *
     * Actual curve: actual_remaining per snapshot_date; missing days are
     * filled by carrying the previous value forward.
     *
     * Edge cases:
     *  - No rows in burndown_daily → returns []
     *  - Single row → returns one point (ideal = total_estimated, actual = actual_remaining)
     *  - All-zero estimates → ideal stays 0 across all points
     *
     * @return BurndownPoint[]
     */
    public function getBurndown(int $projectId, string $iteration): array
    {
        $rows = $this->burndownRepo->getPointsForIteration($projectId, $iteration);

        if (empty($rows)) {
            return [];
        }

        $count           = count($rows);
        $totalEstimated  = (float) $rows[0]['total_estimated'];

        // Build a date → actual_remaining lookup for carry-forward
        $actualByDate = [];
        foreach ($rows as $row) {
            $actualByDate[$row['snapshot_date']] = (float) $row['actual_remaining'];
        }

        $points     = [];
        $lastActual = 0.0;

        foreach ($rows as $i => $row) {
            $date = $row['snapshot_date'];

            // Ideal: linearly interpolate from totalEstimated → 0
            $ideal = $count > 1
                ? round($totalEstimated * (1 - $i / ($count - 1)), 2)
                : $totalEstimated;

            // Actual: use stored value or carry forward last known value
            $actual     = $actualByDate[$date] ?? $lastActual;
            $lastActual = $actual;

            $points[] = new BurndownPoint(
                date:   $date,
                ideal:  $ideal,
                actual: $actual,
            );
        }

        return $points;
    }

    /**
     * Aggregate current issue time data and upsert into burndown_daily for today.
     *
     * Groups issues by iteration. For each iteration, writes:
     *   total_estimated  = SUM(estimated_time)
     *   actual_remaining = SUM(remaining_time)   (ground truth at capture time)
     *   ideal_remaining  = SUM(remaining_time)   (same — ideal line built from history)
     *   open_count       = COUNT WHERE status = 'open'
     *   closed_count     = COUNT WHERE status = 'closed'
     *
     * Idempotent: safe to call multiple times per day. Non-throwing: errors
     * are escalated to the caller (SyncService wraps in try/catch).
     */
    public function captureDaily(int $projectId): void
    {
        $aggregates = $this->issueRepo->aggregateTimeByIteration($projectId);

        foreach ($aggregates as $row) {
            $remaining = (float) $row['total_remaining'];
            $this->burndownRepo->upsertDailySnapshot($projectId, $row['iteration'], [
                'total_estimated'  => (float) $row['total_estimated'],
                'ideal_remaining'  => $remaining,
                'actual_remaining' => $remaining,
                'open_count'       => (int) $row['open_count'],
                'closed_count'     => (int) $row['closed_count'],
            ]);
        }
    }
}
