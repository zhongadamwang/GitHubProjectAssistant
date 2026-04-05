<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\IssueRepository;

/**
 * EfficiencyService — per-member time estimation accuracy analysis.
 *
 * Metrics are derived exclusively from **closed** issues; open issues may have
 * incomplete actual_time and would skew the ratio.
 *
 * Accuracy ratio semantics:
 *   ratio = actual / estimated
 *   1.0  → perfect estimate
 *   > 1.0 → underestimated (took longer than planned)
 *   < 1.0 → overestimated  (finished faster than planned)
 *   null  → insufficient data (estimated = 0 or no closed issues)
 *
 * Source Requirements: R-008
 */
final class EfficiencyService
{
    public function __construct(private readonly IssueRepository $issueRepo)
    {
    }

    /**
     * Return efficiency records for all members on a project.
     *
     * When $iteration is null, aggregates across all iterations.
     * Members with no closed issues in the requested scope are omitted.
     *
     * Returned record shape:
     * [
     *   'member'       => string,   // assignee login
     *   'estimated'    => float,    // SUM(estimated_time) for closed issues
     *   'actual'       => float,    // SUM(actual_time) for closed issues
     *   'ratio'        => float|null,
     *   'issues_count' => int,
     * ]
     *
     * @return array<int,array{member:string,estimated:float,actual:float,ratio:float|null,issues_count:int}>
     */
    public function getMemberEfficiency(int $projectId, ?string $iteration = null): array
    {
        $rows = $this->issueRepo->aggregateEfficiencyByMember($projectId, $iteration);

        return array_map(
            fn(array $row): array => $this->buildRecord($row),
            $rows,
        );
    }

    /**
     * Return per-sprint accuracy ratio history for a single member.
     *
     * Results are ordered by iteration name ascending (alphabetical).
     * Only sprints where the member has at least one closed issue are included.
     *
     * Returned record shape:
     * [
     *   'iteration'    => string,
     *   'estimated'    => float,
     *   'actual'       => float,
     *   'ratio'        => float|null,
     *   'issues_count' => int,
     * ]
     *
     * @return array<int,array{iteration:string,estimated:float,actual:float,ratio:float|null,issues_count:int}>
     */
    public function getMemberTrend(int $projectId, string $member): array
    {
        $allRows = $this->issueRepo->aggregateEfficiencyByMemberAndIteration($projectId);

        $trend = [];
        foreach ($allRows as $row) {
            if ($row['member'] !== $member) {
                continue;
            }

            $trend[] = [
                'iteration'    => $row['iteration'],
                'estimated'    => (float) $row['estimated'],
                'actual'       => (float) $row['actual'],
                'ratio'        => $this->calcRatio((float) $row['estimated'], (float) $row['actual']),
                'issues_count' => (int) $row['issues_count'],
            ];
        }

        return $trend;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Map a raw DB aggregate row to a normalised efficiency record.
     *
     * @param array<string,mixed> $row
     * @return array{member:string,estimated:float,actual:float,ratio:float|null,issues_count:int}
     */
    private function buildRecord(array $row): array
    {
        $estimated = (float) $row['estimated'];
        $actual    = (float) $row['actual'];

        return [
            'member'       => (string) $row['member'],
            'estimated'    => $estimated,
            'actual'       => $actual,
            'ratio'        => $this->calcRatio($estimated, $actual),
            'issues_count' => (int) $row['issues_count'],
        ];
    }

    /**
     * Compute the accuracy ratio.
     *
     * Returns null when estimated = 0 (division by zero would be meaningless).
     * Both estimated and actual must be > 0 for a ratio to be returned.
     */
    private function calcRatio(float $estimated, float $actual): ?float
    {
        if ($estimated <= 0.0 || $actual <= 0.0) {
            return null;
        }

        return round($actual / $estimated, 4);
    }
}
