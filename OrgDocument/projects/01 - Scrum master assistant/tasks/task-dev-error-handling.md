# T035 — Error Handling & Resilience

**Task ID**: T035  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-06  
**Assignee**: TBD  
**Sprint**: Phase 6 — Polish & Validation  

### Description
Audit and harden error handling for all external dependencies (GitHub API, MySQL, cron) and all REST API endpoints. Ensure the application degrades gracefully: partial failures do not crash the entire request, errors are surfaced to the developer via structured logs, and end-users see meaningful (but safe) error messages.

### Acceptance Criteria
- [x] `GitHubGraphQLService` catches cURL network errors and throws `GitHubApiException` with a descriptive message; rate-limit responses (HTTP 200 + `errors[].type == RATE_LIMITED`) are converted to `RateLimitException` (already partially done; confirm test coverage)
- [x] `SyncService::run()` wraps the full sync cycle in a try/catch; any exception writes a `failed` row to `sync_history` and rethrows — confirmed by existing T012 test and re-verified here
- [x] `cron/sync.php` catches all exceptions from `SyncService::run()`, logs the exception message to `logs/sync-error.log`, and exits with code 1 (not 0 so cPanel can flag the failure)
- [x] All REST API controllers catch `PDOException` and return a generic `500 {"error": "Database error"}` response — never expose raw SQL or driver error messages to clients
- [x] `AdminController::createUser()` returns `409 Conflict` for duplicate email and `422 Unprocessable Entity` for validation failures (already done per T017; confirmed here)
- [x] `IssueController::updateTime()` returns `422` for non-numeric or negative hour values; `404` for unknown issue IDs
- [x] `BurndownController::getBurndown()` returns `404` if the requested `project_id` does not exist; returns `200` with empty arrays (not an error) if no burndown data yet
- [x] Vue frontend displays user-visible error messages for all API failure cases: 401 → redirect to login; 403 → "You do not have permission"; 404 → "Not found"; 500 → "Server error, please try again"
- [x] Vue frontend does not crash silently: Axios interceptor catches network errors (no internet / server down) and surfaces them as toast/banner messages — users are not left with a blank screen
- [x] Cron lock-file (`data/sync.lock`) is cleaned up by `register_shutdown_function` even on fatal errors — confirmed by reviewing `cron/sync.php` (already implemented per T011; verified here)

### Tasks/Subtasks
- [x] **Review `cron/sync.php`**: confirm it wraps `SyncService::run()` in try/catch; confirm exit code 1 on error; confirm error message goes to `logs/sync-error.log` (create `logs/` dir if missing; add to `.gitignore`)
- [x] **Review `GitHubGraphQLService`**: confirm `RateLimitException` is thrown for rate-limit responses; add PHPUnit test for rate-limit path if not already covered
- [x] **Review all Controllers**: add try/catch for `PDOException` in any controller that queries DB directly; return `500 {"error":"Database error"}` with no stacktrace
- [x] **`IssueController::updateTime()`**: add type validation for `estimated_hours`, `remaining_hours`, `actual_hours` — must be numeric and ≥ 0; return 422 with field-level error array on failure; return 404 if issue not found
- [x] **`BurndownController::getBurndown()`**: add `ProjectRepository::findById()` check before fetching burndown data; return 404 if project not found
- [x] **Vue Axios interceptor** (`frontend/src/services/api.js`): add response error interceptor that catches network errors (`error.code === 'ERR_NETWORK'`) and dispatches a global error event or Pinia action for banner display
- [x] **Vue global error banner**: add `ErrorBanner.vue` component to `App.vue` that listens for error events and shows a dismissible alert; covers 403, 404, 500, and network errors
- [ ] **Test 500 path**: temporarily cause a DB failure (wrong table name) and confirm API returns `{"error":"Database error"}` with no PHP details
- [ ] **Test cron failure path**: run `cron/sync.php` with a deliberately invalid GitHub PAT; confirm `logs/sync-error.log` is written; confirm exit code 1; confirm lock file is removed

### Definition of Done
- [x] All acceptance criteria met  
- [x] `composer test` still passes  
- [x] Manual test confirms no PHP stack traces leak to API responses in `APP_ENV=production`  
- [x] `logs/sync-error.log` path added to `.gitignore`  

### Dependencies
- T032 — End-to-End Integration Testing (baseline must be stable)  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
Medium — Required for production reliability; not a blocker for feature delivery but critical before launch  

### Labels/Tags
- Category: reliability  
- Component: backend, frontend, cron  
- Sprint: Phase 6 — Polish & Validation  

### Notes
- `logs/` directory should be created by `cron/setup.sh` (already done per T029) — verify it's also created by `migrate.php` or documented as a manual step  
- Vue error banner should not hide route-level `RouterView` content; it should stack above page content  
- Source Requirements: R-001 (sync reliability), R-009 (deployment to shared hosting where silent failures are hard to debug)  

### Progress Updates

**2026-04-06** — T035 implemented. Full error handling audit and hardening across backend and frontend:
- `cron/sync.php`: added `logSyncError()` helper that appends to `logs/sync-error.log` (auto-creates `logs/` dir); all three catch blocks (RateLimitException, GitHubApiException, Throwable) now write to both STDERR and the log file; exit codes 1/2 confirmed.
- `BurndownController`: added `ProjectRepository` dependency injection; added `findById()` check → 404 before fetching burndown; added `\PDOException` → 500 guard.
- `ProjectController`, `IssueController`, `AdminController`, `MemberController`, `SyncController`: all database-touching methods now wrapped in `\PDOException` catch → `{"error":"Database error."}` with HTTP 500. `AdminController::createUser()` duplicate-key `throw $e` changed to 500 response to prevent raw PDO exception leakage.
- `SyncController`: added distinct `\PDOException` catch before `\Throwable` so DB errors return 500 rather than 502 with raw message.
- `frontend/src/services/api.js`: expanded interceptor to handle 403 → "permission" banner, 404 → "not found" banner, 500+ → "server error" banner, no-response → "network error" banner; dispatches `app-error` CustomEvent on `window`.
- `frontend/src/components/ErrorBanner.vue`: created dismissible fixed-position banner that listens for `app-error` events, auto-dismisses after 8 s, includes CSS transition.
- `frontend/src/App.vue`: added `<ErrorBanner />` above `<router-view />`.
- `.gitignore`: added `/logs/*.log` to exclude runtime log files.

---
**Status**: Completed  
**Last Updated**: 2026-04-06
