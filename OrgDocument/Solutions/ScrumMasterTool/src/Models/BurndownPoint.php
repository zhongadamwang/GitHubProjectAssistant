<?php

declare(strict_types=1);

namespace App\Models;

/**
 * BurndownPoint — immutable value object representing a single day on the
 * burndown chart.
 *
 * Used by BurndownService::getBurndown() and serialised to JSON by
 * BurndownController for Chart.js consumption.
 *
 * Properties:
 *   date   — calendar date of this data point (YYYY-MM-DD)
 *   ideal  — ideal remaining hours on this date (linear interpolation)
 *   actual — actual remaining hours recorded in burndown_daily on this date
 *            (carry-forward applied for days with no snapshot row)
 */
final class BurndownPoint
{
    public function __construct(
        public readonly string $date,
        public readonly float  $ideal,
        public readonly float  $actual,
    ) {
    }
}
