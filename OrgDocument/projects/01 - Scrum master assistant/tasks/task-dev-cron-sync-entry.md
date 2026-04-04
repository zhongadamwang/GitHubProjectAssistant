# T011 — Create Cron Sync Entry Point

**Task ID**: T011  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-03  
**Assignee**: TBD  
**Sprint**: Phase 2 — GitHub GraphQL Integration  

### Description
Create the cron-executable PHP script that bootstraps the application, acquires a file-based lock, runs `SyncService::run()`, and exits cleanly. Also implement the admin-triggered manual sync endpoint (`POST /api/sync/trigger`) and the sync history read endpoint (`GET /api/sync/history`).

### Acceptance Criteria
- [x] `cron/sync.php` bootstraps the DI container, resolves `SyncService`, and calls `run()`
- [x] Lock file (`data/sync.lock`) prevents concurrent cron executions; if lock exists and PID is still alive → exit 0 with no error
- [x] Lock file is always removed on exit (success, failure, or exception) — use `register_shutdown_function` or `finally`
- [x] Script exit code: 0 on success, 1 on `GitHubApiException` or `RateLimitException`, 2 on unexpected exception
- [x] Output: writes a single log line to stdout: `[YYYY-MM-DD HH:MM:SS UTC] Sync complete: {added} added, {updated} updated, {unchanged} unchanged` or an error message
- [x] `SyncController::history()` — `GET /api/sync/history` returns last 20 `sync_history` records as JSON array
- [x] `SyncController::trigger()` — `POST /api/sync/trigger` (Admin only) calls `SyncService::run()` synchronously and returns the `SyncResult` as JSON
- [x] `SyncController` wired into DI container and route definitions updated (stub in T006 replaced)

### Tasks/Subtasks
- [x] Create `cron/sync.php` — bootstrap (`require __DIR__ . '/../config/container.php'`), lock-file guard, call `$syncService->run()`, log result, cleanup lock
- [x] Implement lock-file guard: write PID to `data/sync.lock`; on startup, read lock, check if PID is still running (`posix_kill($pid, 0)`)
- [x] Register shutdown function to delete lock file unconditionally
- [x] Create `src/Controllers/SyncController.php` — `history()` and `trigger()` methods; inject `SyncService` and `SyncHistoryRepository`
- [x] Update `config/routes.php` — replace T006 501 stub for sync routes with `SyncController` methods
- [x] Update `config/container.php` — add `SyncController` binding

### Definition of Done
- [x] All acceptance criteria met
- [x] Lock file never left on disk after normal or abnormal exits
- [x] `POST /api/sync/trigger` protected by `AdminMiddleware`
- [ ] Cron script tested by running `php cron/sync.php` from CLI

### Dependencies
- T010 — `SyncService` must be implemented
- T006 — Route definitions (sync routes) must already be defined as stubs

### Effort Estimate
**Time Estimate**: 0.5 day

### Priority
High — Enables automated 15-minute cron sync (ADR-4)

### Labels/Tags
- Category: development
- Component: backend, cron, sync, api
- Sprint: Phase 2 — GitHub GraphQL Integration

### Notes
- cPanel cron command: `php /home/<user>/public_html/cron/sync.php >> /home/<user>/logs/sync.log 2>&1`
- `posix_kill($pid, 0)` returns false if PID is dead; use this to detect stale locks
- `data/` directory must be web-inaccessible on cPanel (not under `public/`); verify `.htaccess` or cPanel settings
- Do NOT use `sleep()` inside the cron script; it must complete and exit promptly
- Source Requirements: R-001, R-002, R-003 — ADR-4

### Progress Updates
- **2026-04-03**: Created `cron/sync.php` — loads autoloader + dotenv from `dirname(__DIR__)`, acquires PID lock at `data/sync.lock` using `posix_kill($pid, 0)` to detect live vs stale locks, registers shutdown function with `@unlink` for unconditional cleanup, resolves `SyncService` from DI container, runs sync, logs `[UTC] Sync complete: X added, Y updated, Z unchanged` to stdout; catches `RateLimitException`/`GitHubApiException` → stderr + exit(1); unexpected `\Throwable` → stderr + exit(2). Replaced stub `SyncController` with real implementation — `history()` reads from `SyncHistoryRepository::findLatest()` (accepts optional `project_id` query param), `trigger()` calls `SyncService::run()` and returns `SyncResult::toArray()` as JSON (502 on any exception). Updated `config/container.php` with real `SyncController` binding injecting `SyncService` + `SyncHistoryRepository`. Routes unchanged — `POST /api/sync/trigger` was already under `AdminMiddleware` from T006.

---
**Status**: Completed
**Last Updated**: 2026-04-03
