<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\GraphQL\Queries;

/**
 * GitHubGraphQLService — executes GitHub GraphQL v4 queries via cURL.
 *
 * Responsibilities:
 *  - Authenticate every request with a Bearer PAT (loaded from settings)
 *  - Retry transient failures (HTTP 5xx, cURL errors) up to MAX_ATTEMPTS times
 *  - Detect rate-limit exhaustion and throw RateLimitException before retrying
 *  - Auto-paginate project items via cursor until hasNextPage is false
 *  - Throw GitHubApiException for non-200 responses or GraphQL error arrays
 *
 * Security: the PAT is never logged, included in exception messages, or
 * stored anywhere outside this class after construction.
 *
 * cURL is used directly to avoid Guzzle/HTTPlug dependencies on cPanel
 * shared hosting (ADR-4).
 */
final class GitHubGraphQLService implements GitHubClientInterface
{
    /** GitHub GraphQL v4 endpoint. */
    private const DEFAULT_ENDPOINT = 'https://api.github.com/graphql';

    /** User-Agent string required by GitHub API ToS. */
    private const USER_AGENT = 'ScrumMasterTool/1.0';

    /** Maximum items fetched per page (GitHub hard limit for ProjectV2Items). */
    private const PAGE_SIZE = 100;

    /** Maximum retry attempts for transient failures before giving up. */
    private const MAX_ATTEMPTS = 3;

    /** Rate-limit threshold — throw RateLimitException when remaining ≤ this. */
    private const RATE_LIMIT_THRESHOLD = 10;

    /** HTTP status codes considered transient (worth retrying). */
    private const RETRYABLE_STATUSES = [500, 502, 503, 504];

    public function __construct(
        private readonly string $pat,
        private readonly string $endpoint = self::DEFAULT_ENDPOINT,
    ) {
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Execute a named GraphQL query and return the decoded response body.
     *
     * @param  string               $queryName  Key defined in App\GraphQL\Queries
     * @param  array<string,mixed>  $variables  GraphQL variables (no PAT here)
     * @return array<string,mixed>              Decoded JSON response (full envelope)
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    public function query(string $queryName, array $variables = []): array
    {
        $queryString = Queries::get($queryName);
        return $this->executeWithRetry($queryString, $variables);
    }

    /**
     * Auto-paginate FETCH_PROJECT_ITEMS until all pages are fetched.
     *
     * @param  string $owner         GitHub login (user or org)
     * @param  int    $projectNumber Project number from the URL
     * @return array<int,array<string,mixed>>  Flat array of project item nodes
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    public function fetchAllProjectItems(string $owner, int $projectNumber): array
    {
        $allNodes = [];
        $cursor   = null;

        do {
            $variables = [
                'owner'  => $owner,
                'number' => $projectNumber,
                'after'  => $cursor,
            ];

            $response = $this->query('FETCH_PROJECT_ITEMS', $variables);

            $items       = $response['data']['user']['projectV2']['items'] ?? [];
            $nodes       = $items['nodes']                                 ?? [];
            $pageInfo    = $items['pageInfo']                              ?? [];
            $hasNextPage = (bool) ($pageInfo['hasNextPage']                ?? false);
            $cursor      = $pageInfo['endCursor']                          ?? null;

            $allNodes = array_merge($allNodes, $nodes);

        } while ($hasNextPage && $cursor !== null);

        return $allNodes;
    }

    /**
     * Verify the PAT is valid by calling FETCH_VIEWER.
     *
     * @return bool  true if a viewer login is returned; false on any exception
     */
    public function checkConnection(): bool
    {
        try {
            $response = $this->query('FETCH_VIEWER');
            return isset($response['data']['viewer']['login'])
                && $response['data']['viewer']['login'] !== '';
        } catch (GitHubApiException | RateLimitException) {
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Internal — retry wrapper
    // -------------------------------------------------------------------------

    /**
     * Execute a raw GraphQL query string with exponential back-off retry on
     * transient failures.
     *
     * Back-off schedule (seconds): 1, 2, 4 …
     *
     * @param  string               $queryString  Raw GraphQL query
     * @param  array<string,mixed>  $variables
     * @return array<string,mixed>
     *
     * @throws GitHubApiException
     * @throws RateLimitException
     */
    private function executeWithRetry(string $queryString, array $variables): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->execute($queryString, $variables);
            } catch (GitHubApiException $e) {
                // Retry only on transient HTTP errors; propagate immediately otherwise
                if (!in_array($e->getHttpStatus(), self::RETRYABLE_STATUSES, true)) {
                    throw $e;
                }

                $lastException = $e;

                if ($attempt < self::MAX_ATTEMPTS) {
                    // Exponential back-off: 1 s, 2 s, 4 s …
                    $sleepSeconds = 2 ** ($attempt - 1);
                    sleep($sleepSeconds);
                }
            }
            // RateLimitException is never retried — propagate immediately
        }

        throw $lastException ?? new GitHubApiException(0, [], 'Max retry attempts exceeded');
    }

    // -------------------------------------------------------------------------
    // Internal — cURL execution
    // -------------------------------------------------------------------------

    /**
     * Perform a single HTTP POST to the GitHub GraphQL endpoint.
     *
     * @param  string               $queryString
     * @param  array<string,mixed>  $variables
     * @return array<string,mixed>  Full decoded JSON envelope
     *
     * @throws GitHubApiException   on non-200 HTTP or GraphQL errors array
     * @throws RateLimitException   when remaining points ≤ RATE_LIMIT_THRESHOLD
     */
    private function execute(string $queryString, array $variables): array
    {
        $body = json_encode([
            'query'     => $queryString,
            'variables' => $variables,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->pat,
                'User-Agent: ' . self::USER_AGENT,
                'Accept: application/vnd.github+json',
            ],
            // Capture response headers for rate-limit inspection
            CURLOPT_HEADER         => true,
        ]);

        $raw        = curl_exec($ch);
        $curlErrNo  = curl_errno($ch);
        $curlErrMsg = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // cURL-level failure (network error, DNS, timeout, etc.)
        if ($curlErrNo !== 0 || $raw === false) {
            throw new GitHubApiException(
                0,
                [],
                'cURL error ' . $curlErrNo . ': ' . $curlErrMsg,
            );
        }

        // Separate headers from body
        $rawHeaders = substr((string) $raw, 0, $headerSize);
        $rawBody    = substr((string) $raw, $headerSize);

        // Check rate-limit header before looking at the body
        $remaining = $this->parseRateLimitRemaining($rawHeaders);
        $resetAt   = $this->parseRateLimitReset($rawHeaders);

        if ($remaining !== null && $remaining <= self::RATE_LIMIT_THRESHOLD) {
            throw new RateLimitException($remaining, $resetAt ?? (time() + 3600));
        }

        // Non-2xx HTTP response
        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new GitHubApiException(
                $httpStatus,
                [],
                sprintf('GitHub API returned HTTP %d', $httpStatus),
            );
        }

        // Decode JSON body
        try {
            /** @var array<string,mixed> $decoded */
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new GitHubApiException(
                $httpStatus,
                [],
                'Failed to decode GitHub API response: ' . $e->getMessage(),
            );
        }

        // GraphQL-level errors (HTTP 200 but response contains "errors" key)
        if (!empty($decoded['errors'])) {
            throw GitHubApiException::fromGraphqlErrors($httpStatus, $decoded['errors']);
        }

        return $decoded;
    }

    // -------------------------------------------------------------------------
    // Internal — header parsing helpers
    // -------------------------------------------------------------------------

    /**
     * Extract X-RateLimit-Remaining from raw HTTP response headers.
     *
     * @param  string   $rawHeaders  The raw header section of the HTTP response
     * @return int|null              null if header absent
     */
    private function parseRateLimitRemaining(string $rawHeaders): ?int
    {
        return $this->parseIntHeader($rawHeaders, 'X-RateLimit-Remaining');
    }

    /**
     * Extract X-RateLimit-Reset from raw HTTP response headers.
     *
     * @param  string   $rawHeaders
     * @return int|null              null if header absent
     */
    private function parseRateLimitReset(string $rawHeaders): ?int
    {
        return $this->parseIntHeader($rawHeaders, 'X-RateLimit-Reset');
    }

    /**
     * Generic case-insensitive integer header extractor.
     *
     * @param  string $rawHeaders
     * @param  string $headerName
     * @return int|null
     */
    private function parseIntHeader(string $rawHeaders, string $headerName): ?int
    {
        $pattern = '/^' . preg_quote($headerName, '/') . ':\s*(\d+)/im';

        if (preg_match($pattern, $rawHeaders, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
