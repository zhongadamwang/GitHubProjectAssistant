<?php

declare(strict_types=1);

namespace App\Services;

/**
 * SecretRedactor — defence in depth: strips anything that looks like a GitHub token from text
 * before it is stored (sync history, account status) or returned in an API response.
 */
final class SecretRedactor
{
    private const PATTERN = '/\b(?:gh[pousr]_[A-Za-z0-9_]{16,}|github_pat_[A-Za-z0-9_]{16,})\b|Bearer\s+[A-Za-z0-9._\-]{16,}/i';

    public static function redact(string $text): string
    {
        return preg_replace(self::PATTERN, '[redacted]', $text) ?? '[redacted]';
    }
}
