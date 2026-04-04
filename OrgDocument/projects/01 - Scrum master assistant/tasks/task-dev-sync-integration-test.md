# T012 — Integration Test: GitHub Sync End-to-End

**Task ID**: T012  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Validate the complete sync pipeline end-to-end against a real GitHub project or a recorded fixture. Covers the full chain: `GitHubGraphQLService` → `ResponseParser` → `SyncService` → DB upsert → `sync_history` write → snapshot file creation.

### Acceptance Criteria
- [ ] Integration test suite in `tests/Integration/SyncIntegrationTest.php` using PHPUnit
- [ ] **Fixture mode** (CI-safe): a recorded JSON fixture in `tests/fixtures/github-project-response.json` replaces the live API call; `GitHubGraphQLService` is swapped for a mock/stub that returns the fixture
- [ ] **Live mode** (optional, manual): controlled by `GITHUB_INTEGRATION_TEST=true` env var — executes against the real GitHub API; skipped by default in CI
- [ ] Test: `SyncService::run()` with mock API returns a `SyncResult` with correct `added` / `updated` / `unchanged` counts for the fixture data
- [ ] Test: a second run of `SyncService::run()` with the same fixture data returns `added=0`, `updated=0`, `unchanged=N` (idempotency)
- [ ] Test: `sync_history` table contains exactly one row after the first run, two after the second
- [ ] Test: snapshot file is created at the correct path with valid JSON content
- [ ] Test: if `GitHubGraphQLService` throws `GitHubApiException`, `SyncService` catches it, writes a `failed` sync_history record, and rethrows
- [ ] Test: local time fields (`estimated_hours`, `remaining_hours`, `actual_hours`) are preserved after a re-sync of the same issue

### Tasks/Subtasks
- [ ] Create `tests/fixtures/github-project-response.json` — realistic fixture with 5 issues covering different states (open, closed) and field types (text, number, date, single-select, null)
- [ ] Create `tests/Integration/SyncIntegrationTest.php` — extends PHPUnit `TestCase`; uses in-memory SQLite or a dedicated test MySQL DB (controlled by `TEST_DB_*` env vars)
- [ ] Bootstrap test DB before each test: run migrations, truncate `issues`, `projects`, `sync_history`
- [ ] Write stub `GitHubGraphQLServiceStub` returning fixture data; inject via constructor arg
- [ ] Implement test: first-run counts match fixture
- [ ] Implement test: second-run idempotency
- [ ] Implement test: API failure → `failed` history record
- [ ] Implement test: local time fields preserved
- [ ] Document how to run live-mode test in `tests/Integration/README.md`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] All tests pass with `composer test` (fixture mode, no network required)
- [ ] Snapshot file cleaned up after each test run (or uses temp directory)
- [ ] No real PAT or secrets in fixture files

### Dependencies
- T010 — `SyncService` must be complete
- T011 — `cron/sync.php` must be runnable; cron entry point is smoke-tested via CLI in this task

### Effort Estimate
**Time Estimate**: 0.5 day

### Priority
High — Provides confidence gate before Phase 3 and Phase 4 work begins

### Labels/Tags
- Category: testing
- Component: backend, sync, integration-test
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- Prefer SQLite (`:memory:`) for integration tests to avoid requiring a MySQL server in CI; migration SQL must be compatible with SQLite (check for MySQL-specific syntax in migration files)
- If SQLite compatibility is not feasible, use a Docker-based MySQL in CI or document manual setup
- Fixture JSON must match the exact shape returned by `FETCH_PROJECT_ITEMS` (including `pageInfo`, `fieldValues.nodes` with inline fragments)
- Source Requirements: R-001, R-002, R-003 — ADR-4, ADR-5

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-03
