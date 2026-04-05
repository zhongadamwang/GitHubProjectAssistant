<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * RateLimitException — thrown when the GitHub API signals that very few
 * API points remain (X-RateLimit-Remaining ≤ 10).
 *
 * Callers should catch this and abort the current sync cycle, scheduling
 * a retry after the reset timestamp.
 */
final class RateLimitException extends RuntimeException
{
    /**
     * @param int $remaining   Points remaining in the current rate-limit window
     * @param int $resetAt     Unix timestamp when the window resets (from X-RateLimit-Reset)
     */
    public function __construct(
        private readonly int $remaining,
        private readonly int $resetAt,
    ) {
        parent::__construct(sprintf(
            'GitHub rate limit nearly exhausted: %d points remaining; resets at %s UTC',
            $this->remaining,
            gmdate('Y-m-d H:i:s', $this->resetAt),
        ));
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getResetAt(): int
    {
        return $this->resetAt;
    }

    /** Seconds until the rate-limit window resets (0 if already past). */
    public function getSecondsUntilReset(): int
    {
        return max(0, $this->resetAt - time());
    }
}
