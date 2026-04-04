# T010 — Build Sync Logic with Diff & Snapshot

**Task ID**: T010  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Implement the core sync orchestration that coordinates fetching, parsing, diffing, persisting, and snapshotting GitHub project data. The `SyncService` ties together `GitHubGraphQLService`, `ResponseParser`, the database repositories, and the file-based snapshot store in a single transactional operation.

### Acceptance Criteria
- [ ] `SyncService::run(): SyncResult` executes a full sync cycle and returns a `SyncResult` carrying counts (added, updated, unchanged, errors) and a status string
- [ ] Sync flow order: (1) fetch all project items via `GitHubGraphQLService`, (2) parse with `ResponseParser`, (3) diff against DB state, (4) upsert changed issues, (5) write snapshot JSON, (6) write `sync_history` record
- [ ] Diff logic: compares `updatedAt` from GitHub against `github_updated_at` stored in `issues` table; only issues where GitHub `updatedAt` is newer are upserted
- [ ] Issues new to the local DB are inserted with `estimated_hours`, `remaining_hours`, `actual_hours` all set to `null` (preserved from any existing local values on update — never overwritten by sync)
- [ ] Snapshot file written to `data/snapshots/YYYY-MM-DD_HH-mm.json` with full raw payload (project + all items); file name uses UTC time
- [ ] `sync_history` row inserted with: `synced_at`, `status` (`success`/`partial`/`failed`), `issues_added`, `issues_updated`, `snapshot_file`, `error_message`
- [ ] If GitHub API call fails (any exception from `GitHubGraphQLService`), sync logs a `failed` record to `sync_history` and rethrows
- [ ] Project row in `projects` table upserted on every sync (title, description, last_synced_at)

### Tasks/Subtasks
- [ ] Create `src/Services/SyncService.php` — constructor injects `GitHubGraphQLService`, `ResponseParser`, `ProjectRepository`, `IssueRepository`, `SyncHistoryRepository`
- [ ] Implement `run()`: fetch → parse → diff → upsert loop → snapshot write → history write
- [ ] Create `src/Repositories/ProjectRepository.php` — `upsertFromGitHub(Project $project): void`, `findByGitHubId(string $id): ?array`
- [ ] Create `src/Repositories/IssueRepository.php` — `upsertFromGitHub(Issue $issue): void`, `findByGitHubId(string $id): ?array`; never overwrite local time fields on update
- [ ] Create `src/Repositories/SyncHistoryRepository.php` — `insert(array $record): void`, `findLatest(int $limit = 20): array`
- [ ] Implement snapshot writer: `data/snapshots/` directory auto-created if missing; filename pattern `YYYY-MM-DD_HH-mm.json`; JSON-encode full raw response
- [ ] Wire `ProjectRepository`, `IssueRepository`, `SyncHistoryRepository`, `SyncService` into `config/container.php`
- [ ] Write unit test: full sync run with mock service returning 3 items — 1 new, 1 updated, 1 unchanged

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Local time fields (`estimated_hours`, `remaining_hours`, `actual_hours`) are never overwritten by sync
- [ ] `sync_history` always written (success or failure)
- [ ] Snapshot directory created automatically on first run
- [ ] All DB operations use PDO prepared statements

### Dependencies
- T002 — `issues`, `projects`, `sync_history` tables must exist
- T008 — `GitHubGraphQLService` must be implemented
- T009 — `ResponseParser` and domain models (`Project`, `Issue`) must be implemented

### Effort Estimate
**Time Estimate**: 1.5 days

### Priority
High — Critical path; blocks T011 (Cron Entry Point) and T012 (Integration Test)

### Labels/Tags
- Category: development
- Component: backend, sync, database, snapshot
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- The diff strategy (compare `updatedAt`) is intentionally simple; a hash-based approach would be more robust but adds complexity not justified for this team size
- Snapshot files are the authoritative historical record (ADR-5); DB is the queryable working copy
- Concurrency: lock-file guard lives in T011 (cron entry), not in `SyncService` itself
- `IssueRepository::upsertFromGitHub()` must use `INSERT ... ON DUPLICATE KEY UPDATE` (MySQL) with explicit column exclusions for `estimated_hours`, `remaining_hours`, `actual_hours`
- Source Requirements: R-001, R-002, R-003, R-004 — ADR-4, ADR-5

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-03
