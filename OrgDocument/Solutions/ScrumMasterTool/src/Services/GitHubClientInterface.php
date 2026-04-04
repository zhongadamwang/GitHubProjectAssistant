<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;

/**
 * GitHubClientInterface — contract for GitHub GraphQL API clients.
 *
 * Extracting this interface allows SyncService to be tested against
 * a stub without requiring a real network connection.
 */
interface GitHubClientInterface
{
    /**
     * Execute a named GraphQL query and return the decoded response body.
     *
     * @param  string               $queryName  Key in App\GraphQL\Queries
     * @param  array<string,mixed>  $variables
     * @return array<string,mixed>
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    public function query(string $queryName, array $variables = []): array;

    /**
     * Auto-paginate FETCH_PROJECT_ITEMS until all pages are fetched.
     *
     * @param  string $owner
     * @param  int    $projectNumber
     * @return array<int,array<string,mixed>>  Flat array of project item nodes
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    public function fetchAllProjectItems(string $owner, int $projectNumber): array;

    /**
     * Return true if the configured PAT is valid and the API is reachable.
     */
    public function checkConnection(): bool;
}
