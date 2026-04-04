# T008 — Implement GitHubGraphQLService

**Task ID**: T008  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Build the `GitHubGraphQLService` class responsible for executing GraphQL queries against the GitHub API. Must handle authentication, pagination across all project items, rate-limit detection, transient error retries, and structured error propagation.

### Acceptance Criteria
- [ ] `GitHubGraphQLService` constructor accepts the GitHub PAT and project owner/number (loaded from settings)
- [ ] `query(string $queryName, array $variables): array` executes a named query from `queries.php` and returns decoded JSON
- [ ] `fetchAllProjectItems(string $owner, int $projectNumber): array` auto-paginates via `endCursor` until `hasNextPage` is false; returns merged flat array of nodes
- [ ] `checkConnection(): bool` calls `FETCH_VIEWER` and returns true if a login is returned
- [ ] Rate limit: reads `X-RateLimit-Remaining` header; throws `RateLimitException` when ≤ 10 remaining
- [ ] Transient retry: on HTTP 5xx or curl error, retries up to 3 times with 1-second exponential back-off
- [ ] Non-200 responses or GraphQL-level `errors` array entries are thrown as `GitHubApiException` with full error context
- [ ] All HTTP calls use PHP cURL (no Guzzle) to minimise dependencies on shared hosting

### Tasks/Subtasks
- [ ] Create `src/Services/GitHubGraphQLService.php` with typed constructor (PAT, endpoint URL from settings)
- [ ] Implement private `execute(string $query, array $variables): array` — cURL POST to `https://api.github.com/graphql`, JSON body, Bearer auth header, User-Agent header
- [ ] Implement public `query(string $queryName, array $variables): array` — looks up query from `queries.php`, delegates to `execute()`
- [ ] Implement retry wrapper: up to 3 attempts on curl error or HTTP 500/502/503/504
- [ ] Implement `fetchAllProjectItems()` pagination loop using `pageInfo.endCursor`
- [ ] Implement `checkConnection()` — calls `FETCH_VIEWER`, returns bool
- [ ] Create `src/Exceptions/GitHubApiException.php` — carries HTTP status, GraphQL error messages
- [ ] Create `src/Exceptions/RateLimitException.php` — carries remaining and reset timestamp
- [ ] Wire `GitHubGraphQLService` into `config/container.php` with PAT + project config from settings
- [ ] Write unit test: mock cURL responses for success, 5xx retry, rate-limit, and GraphQL errors

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Secrets (PAT) never logged or included in exception messages
- [ ] cURL `CURLOPT_TIMEOUT` set to 30 s; `CURLOPT_CONNECTTIMEOUT` to 10 s
- [ ] `User-Agent` header set (`ScrumMasterTool/1.0`)
- [ ] Container wired and resolvable

### Dependencies
- T007 — `src/GraphQL/queries.php` must define named query constants
- T001 — Project scaffold and DI container config must exist

### Effort Estimate
**Time Estimate**: 1.5 days

### Priority
High — Blocks T010 (Sync Logic) and T012 (Integration Test)

### Labels/Tags
- Category: development
- Component: backend, github-api, graphql, http-client
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- Do NOT use Guzzle; cURL is available on all PHP 8.x cPanel installs
- PAT must be stored in `.env` as `GITHUB_TOKEN`; never committed
- Project owner + number stored in `.env` as `GITHUB_PROJECT_OWNER` and `GITHUB_PROJECT_NUMBER`
- GitHub requires a `User-Agent` header on all API calls
- Rate limit window is 1 hour; 5,000 points total; each query costs ~10–20 points (ADR-4)
- Source Requirements: R-001, R-002 — ADR-4

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-03
