<?php

declare(strict_types=1);

namespace App\Services;

/**
 * GitHubAccountClientInterface — the extra GitHub operations needed for account
 * administration and sync orchestration on top of the basic sync client.
 */
interface GitHubAccountClientInterface extends GitHubClientInterface
{
    /**
     * 'Organization' or 'User' for a login, or null when the login does not exist.
     *
     * @throws \App\Exceptions\GitHubApiException
     * @throws \App\Exceptions\RateLimitException
     */
    public function detectOwnerType(string $owner): ?string;

    /**
     * Projects (v2) of an owner visible to the token.
     *
     * @return array<int,array{id:string,number:int,title:string,closed:bool,url:?string,shortDescription:?string}>
     *
     * @throws \App\Exceptions\GitHubApiException
     * @throws \App\Exceptions\RateLimitException
     */
    public function listOwnerProjects(string $owner): array;

    /**
     * Login of the authenticated user (token owner), or null when it cannot be determined.
     *
     * @throws \App\Exceptions\GitHubApiException
     * @throws \App\Exceptions\RateLimitException
     */
    public function viewerLogin(): ?string;

    /**
     * Metadata of the most recent response:
     *   rate_remaining   int|null   points left in the current window
     *   token_expires_at string|null 'Y-m-d H:i:s' UTC (fine-grained/classic tokens with expiry)
     *   scopes           string[]|null OAuth scopes of a classic token (null when not reported)
     *
     * @return array{rate_remaining: ?int, token_expires_at: ?string, scopes: ?array<int,string>}
     */
    public function lastMeta(): array;
}
