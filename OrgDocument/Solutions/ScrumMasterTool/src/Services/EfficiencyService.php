<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\IssueRepository;

/**
 * EfficiencyService — per-member time estimation accuracy analytics.
 *
 * Only **closed** issues contribute to metrics; open issues may have
 * incomplete actual_time values.
 *
 * ratio > 1.0  → member underestimated (took longer than expected)
 * ratio < 1.0  → member overestimated  (finished earlier than expected)
 * ratio = null → estimated = 0 (can't compute a ratio)
 */
final class EfficiencyService
{
    public function __construct(private readonly IssueRepository $issueRepo)
    {
    }

    /**
     * Return per-member efficiency for a project, optionally scoped to one iteration.
     *
     * Each record:
     *   member       - assignee login string
     *   estimated    - SUM(estimated_time) for closed issues
     *   actual       - SUM(actual_time) for closed issues
     *   ratio        - actual/estimated (null when estimated = 0)
     *   issues_count - count of closed issues assigned to this member
     *
     * Members with no closed issues in the requested scope are omitted.
     *
     * @return array<int, array{member: string, estimated: float, actual: float, ratio: float|null, issues_count: int}>
     */
    public function getMemberEfficiency(int $projectId, ?string $iteration = null): array
    {
        $rows = $this->issueRepo->aggregateEfficiencyByMember($projectId, $iteration);

        return array_map([$this, 'buildRecord'], $rows);
    }

    /**
     * Return per-sprint accuracy history for a specific member, ordered by iteration ASC.
     *
     * @return array<int, array{iteration: string, estimated: float, actual: float, ratio: float|null, issues_count: int}>
     */
    public function getMemberTrend(int $projectId, string $member): array
    {
        $rows = $this->issueRepo->aggregateEfficiencyByMemberAndIteration($projectId);

        $memberRows = array_filter(
            $rows,
            static fn(array $r): bool => ($r['assignee'] ?? '') === $member,
        );

        usort($memberRows, static fn(array $a, array $b): int => strcmp($a['iteration'], $b['iteration']));

        return array_values(array_map(function (array $row): array {
            return [
                'iteration'   => $row['iteration'],
                'estimated'   => (float) $row['total_estimated'],
                'actual'      => (float) $row['total_actual'],
                'ratio'       => $this->ratio((float) $row['total_estimated'], (float) $row['total_actual']),
                'issues_count' => (int) $row['issues_count'],
            ];
        }, $memberRows));
    }

    // -------------------------------------------------------------------------

    private function buildRecord(array $row): array
    {
        $estimated = (float) $row['total_estimated'];
        $actual    = (float) $row['total_actual'];

        return [
            'member'       => $row['assignee'],
            'estimated'    => $estimated,
            'actual'       => $actual,
            'ratio'        => $this->ratio($estimated, $actual),
            'issues_count' => (int) $row['issues_count'],
        ];
    }

    private function ratio(float $estimated, float $actual): ?float
    {
        if ($estimated <= 0.0) {
            return null;
        }
        return round($actual / $estimated, 4);
    }
}
