<?php

declare(strict_types=1);

namespace App\Models;

/**
 * BurndownPoint — immutable value object for a single burndown chart data point.
 *
 * Consumed by the Vue Chart.js burndown line chart on the frontend.
 */
final class BurndownPoint
{
    public function __construct(
        public readonly string $date,   // YYYY-MM-DD
        public readonly float  $ideal,
        public readonly float  $actual,
    ) {
    }

    public function toArray(): array
    {
        return [
            'date'   => $this->date,
            'ideal'  => $this->ideal,
            'actual' => $this->actual,
        ];
    }
}
