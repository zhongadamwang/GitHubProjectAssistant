<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8\Support;

use App\Services\GitHubAccountClientInterface;

/**
 * FakeGitHubClient — scriptable stand-in for GitHub (no network).
 *
 * Configure the public properties; set $failWith to make every call throw.
 * $calls records the operations performed, in order.
 */
final class FakeGitHubClient implements GitHubAccountClientInterface
{
    /** @var array{rate_remaining: ?int, token_expires_at: ?string, scopes: ?array<int,string>} */
    public array $meta = ['rate_remaining' => 4000, 'token_expires_at' => null, 'scopes' => null];

    /** @var string[] */
    public array $calls = [];

    /**
     * Full project fixture (tests/fixtures/github-project-response.json shape): when set, the
     * client behaves like a GitHub project for sync runs.
     *
     * @var array<string,mixed>|null
     */
    public ?array $fixture = null;

    /**
     * Field definitions returned for FETCH_PROJECT_FIELDS: [ ['id'=>…, 'name'=>…, 'dataType'=>…], … ]
     *
     * @var array<int,array<string,mixed>>
     */
    public array $projectFields = [];

    /**
     * @param array<int,array{id:string,number:int,title:string,closed:bool,url:?string,shortDescription:?string}> $projects
     */
    public function __construct(
        public string      $viewer    = 'octocat',
        public ?string     $ownerType = 'Organization',
        public array       $projects  = [],
        public ?\Throwable $failWith  = null,
    ) {
    }

    public function query(string $queryName, array $variables = []): array
    {
        $this->calls[] = 'query:' . $queryName;
        $this->maybeFail();

        if ($queryName === 'FETCH_PROJECT_FIELDS' && $this->fixture !== null) {
            return $this->fixture['project_fields_response'];
        }

        if ($queryName === 'FETCH_PROJECT_FIELDS') {
            return ['data' => ['organization' => ['projectV2' => [
                'id' => 'PVT_FAKE', 'title' => 'Fake', 'number' => (int) ($variables['number'] ?? 1),
                'fields' => ['nodes' => $this->projectFields],
            ]]]];
        }

        return [];
    }

    public function fetchAllProjectItems(string $owner, int $projectNumber): array
    {
        $this->calls[] = 'items';
        $this->maybeFail();

        return $this->fixture['project_items_nodes'] ?? [];
    }

    public function checkConnection(): bool
    {
        return $this->failWith === null;
    }

    public function detectOwnerType(string $owner): ?string
    {
        $this->calls[] = 'detectOwnerType';
        $this->maybeFail();

        return $this->ownerType;
    }

    public function listOwnerProjects(string $owner): array
    {
        $this->calls[] = 'listOwnerProjects';
        $this->maybeFail();

        return $this->projects;
    }

    public function viewerLogin(): ?string
    {
        $this->calls[] = 'viewer';
        $this->maybeFail();

        return $this->viewer;
    }

    public function lastMeta(): array
    {
        return $this->meta;
    }

    private function maybeFail(): void
    {
        if ($this->failWith !== null) {
            throw $this->failWith;
        }
    }
}
