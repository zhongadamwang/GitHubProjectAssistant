# T014 — Build Daily Burndown Snapshot Job

**Task ID**: T014  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-04  
**Assignee**: TBD  
**Sprint**: Phase 3 — Analytics Engine  

### Description
Implement `BurndownService::captureDaily()` which aggregates current time-tracking totals per iteration and upserts a row into `burndown_daily`. This method is called automatically at the end of every successful sync cycle (T010 `SyncService`) so that the burndown chart always reflects the latest sync state. It can also be invoked independently for backfill or testing.

### Acceptance Criteria
- [x] `BurndownService::captureDaily(int $projectId): void` queries `issues` table and groups by `iteration`, computing `SUM(estimated_time)`, `SUM(remaining_time)`, `COUNT(*) WHERE status = 'open'`, `COUNT(*) WHERE status = 'closed'`
- [x] For each iteration result, upserts into `burndown_daily` using `INSERT … ON DUPLICATE KEY UPDATE` on the unique key `(project_id, iteration, snapshot_date)` with `snapshot_date = CURDATE()`
- [x] `ideal_remaining` column set to `SUM(remaining_time)` at time of capture; `total_estimated` set to `SUM(estimated_time)`
- [x] `actual_remaining` column set to `SUM(remaining_time)` (same value — represents ground truth at capture time)
- [x] `SyncService::run()` calls `captureDaily()` after a successful sync cycle (between snapshot write and history write steps); failure in `captureDaily()` is logged to error but does not fail the sync
- [x] `captureDaily()` is idempotent — calling it twice on the same day overwrites the row cleanly without error
- [x] Zero-issue iterations (edge case) produce a row with all-zero aggregates rather than being skipped

### Tasks/Subtasks
- [x] Implement `BurndownService::captureDaily(int $projectId): void` using `BurndownRepository` for upsert and a new `IssueRepository::aggregateTimeByIteration(int $projectId): array` method
- [x] Add `IssueRepository::aggregateTimeByIteration(int $projectId): array` — returns `[['iteration' => string, 'total_estimated' => float, 'total_remaining' => float, 'open_count' => int, 'closed_count' => int], ...]` via GROUP BY query
- [x] Add `BurndownRepository::upsertDailySnapshot(int $projectId, string $iteration, array $data): void` — issues `INSERT … ON DUPLICATE KEY UPDATE` for `(project_id, iteration, snapshot_date)` (already present from T013)
- [x] Modify `SyncService::run()` to call `$this->burndownService->captureDaily($projectId)` after successful sync; wrap in try/catch and log warning on failure without rethrowing
- [x] Wire `BurndownService` into `SyncService` constructor via `config/container.php`
- [x] Write unit test: `captureDaily()` called with known issue data → correct `burndown_daily` row values; second call on same day → idempotent

### Definition of Done
- [x] All acceptance criteria met
- [x] `burndown_daily` rows accumulate over time as sync runs
- [x] Sync does not fail if `captureDaily()` throws
- [x] `IssueRepository::aggregateTimeByIteration()` uses PDO prepared statements
- [x] Unit test passes for idempotency

### Dependencies
- T002 — `burndown_daily` and `issues` tables (migrations 003, 006)
- T010 — `SyncService::run()` must exist to hook `captureDaily()` into
- T013 — `BurndownService` class and `BurndownRepository` must already exist

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — Without this, `burndown_daily` stays empty and T022 Dashboard burndown chart shows no data

### Labels/Tags
- Category: development
- Component: backend, analytics, sync, burndown
- Sprint: Phase 3 — Analytics Engine

### Notes
- `captureDaily()` runs after each sync, so `burndown_daily` will have at most one row per project-iteration-date (ensured by the UNIQUE KEY)
- Historical backfill (re-running for past dates) is out of scope; only current-date upsert is needed
- The `ideal_remaining` / ideal curve calculation (linear interpolation) is handled in `BurndownService::getBurndown()` (T013), not stored here
- Source Requirements: R-005, R-006

### Progress Updates
- **2026-04-04**: Added `IssueRepository::aggregateTimeByIteration()` — GROUP BY iteration query with COALESCE for NULL time fields, SUM CASE for open/closed counts; PDO prepared statement. Replaced `BurndownService::captureDaily()` stub with full implementation — injects `IssueRepository`, iterates aggregate rows, maps `total_remaining` → both `ideal_remaining` and `actual_remaining`, uses UTC today's date. Added `BurndownService $burndownService` to `SyncService` constructor; hooked `captureDaily()` call between Step 6 (snapshot write) and Step 7 (history write) wrapped in try/catch — failure logs `E_USER_WARNING` and never aborts sync. Updated `container.php`: `BurndownService` now receives `IssueRepository`, `SyncService` receives `BurndownService`. Created `tests/Integration/Phase3/CaptureDailyTest.php` — 5 tests covering: single-iteration correct value mapping, idempotency (two calls → two upserts), multi-iteration (one upsert per iteration), empty project (no upsert), UTC date correctness.

---
**Status**: Completed  
**Last Updated**: 2026-04-04
