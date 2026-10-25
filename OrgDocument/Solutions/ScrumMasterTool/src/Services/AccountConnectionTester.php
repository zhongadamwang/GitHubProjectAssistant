<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;

/**
 * AccountConnectionTester — checks a GitHub token and turns the outcome (or any
 * failure) into an account status:
 *
 *   ok | invalid | expired | insufficient_scope | unreachable
 *
 * Messages are short, human-readable and never contain the token.
 */
final class AccountConnectionTester
{
    /**
     * Test a client: viewer lookup, token expiry/scopes, and access to the owner's projects.
     *
     * @return array{status: string, message: string, verified_at: string, expires_at: ?string, viewer: ?string, projects: ?int}
     */
    public function test(GitHubAccountClientInterface $client, string $ownerLogin): array
    {
        $now = gmdate('Y-m-d H:i:s');

        try {
            $viewer = $client->viewerLogin();

            if ($viewer === null) {
                return $this->result('invalid', 'GitHub did not recognise this token.', $now);
            }

            $meta    = $client->lastMeta();
            $expires = $meta['token_expires_at'];

            if ($expires !== null && strtotime($expires . ' UTC') < time()) {
                return $this->result('expired', 'The token expired on ' . $expires . ' UTC.', $now, $expires, $viewer);
            }

            $scopes = $meta['scopes'];
            if ($scopes !== null && !in_array('read:project', $scopes, true) && !in_array('project', $scopes, true)) {
                return $this->result(
                    'insufficient_scope',
                    'The token is missing the "read:project" scope (and "read:org" for organization projects).',
                    $now,
                    $expires,
                    $viewer,
                );
            }

            $projects = $client->listOwnerProjects($ownerLogin);
            $count    = count($projects);

            return $this->result(
                'ok',
                sprintf('Connected as %s; %d project%s visible for %s.', $viewer, $count, $count === 1 ? '' : 's', $ownerLogin),
                $now,
                $meta['token_expires_at'] ?? $expires,
                $viewer,
                $count,
            );
        } catch (\Throwable $e) {
            return $this->classify($e, $now);
        }
    }

    /**
     * Map a failure to an account status.
     *
     * @return array{status: string, message: string, verified_at: string, expires_at: ?string, viewer: ?string, projects: ?int}
     */
    public function classify(\Throwable $e, ?string $now = null): array
    {
        $now ??= gmdate('Y-m-d H:i:s');

        if ($e instanceof RateLimitException) {
            return $this->result('unreachable', 'GitHub rate limit reached — try again later.', $now);
        }

        if ($e instanceof GitHubApiException) {
            $status  = $e->getHttpStatus();
            $message = $e->getMessage();

            if ($status === 0) {
                return $this->result('unreachable', 'Could not reach GitHub (network error).', $now);
            }
            if ($status === 401) {
                return preg_match('/expired/i', $message) === 1
                    ? $this->result('expired', 'The token has expired.', $now)
                    : $this->result('invalid', 'GitHub rejected the token (bad credentials).', $now);
            }
            if ($status === 403) {
                return $this->result('insufficient_scope', 'Access forbidden — the token lacks permission or needs SSO authorisation.', $now);
            }
            if (preg_match('/scope|not accessible|permission|forbidden/i', $message) === 1) {
                return $this->result('insufficient_scope', 'The token lacks the required permissions for this owner.', $now);
            }
            if (preg_match('/Could not resolve to an? (Organization|User)/i', $message) === 1) {
                return $this->result('invalid', 'The owner was not found with this type — check the owner name.', $now);
            }

            return $this->result('unreachable', 'GitHub request failed: ' . mb_substr($message, 0, 160), $now);
        }

        return $this->result('unreachable', 'Connection test failed unexpectedly.', $now);
    }

    /**
     * @return array{status: string, message: string, verified_at: string, expires_at: ?string, viewer: ?string, projects: ?int}
     */
    private function result(string $status, string $message, string $now, ?string $expires = null, ?string $viewer = null, ?int $projects = null): array
    {
        return [
            'status'      => $status,
            'message'     => mb_substr(SecretRedactor::redact($message), 0, 255),
            'verified_at' => $now,
            'expires_at'  => $expires,
            'viewer'      => $viewer,
            'projects'    => $projects,
        ];
    }
}
