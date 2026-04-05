<?php

declare(strict_types=1);

namespace Tests\Integration\Phase2;

use App\Services\GitHubClientInterface;

/**
 * GitHubGraphQLServiceStub — returns pre-recorded fixture data for tests.
 *
 * Implements GitHubClientInterface so it can be injected into SyncService
 * without any network calls.
 *
 * The fixture array must contain:
 *   'project_fields_response'  → full JSON envelope for query('FETCH_PROJECT_FIELDS', ...)
 *   'project_items_nodes'      → flat array of project item nodes for fetchAllProjectItems()
 */
final class GitHubGraphQLServiceStub implements GitHubClientInterface
{
    /** @param array<string,mixed> $fixture */
    public function __construct(private readonly array $fixture)
    {
    }

    /** {@inheritdoc} */
    public function query(string $queryName, array $variables = []): array
    {
        return match ($queryName) {
            'FETCH_PROJECT_FIELDS' => $this->fixture['project_fields_response'],
            'FETCH_VIEWER'         => ['data' => ['viewer' => ['login' => 'testowner']]],
            default                => ['data' => []],
        };
    }

    /** {@inheritdoc} */
    public function fetchAllProjectItems(string $owner, int $projectNumber): array
    {
        return $this->fixture['project_items_nodes'] ?? [];
    }

    /** {@inheritdoc} */
    public function checkConnection(): bool
    {
        return true;
    }
}
