# T016 — Implement TimeTrackingService with Audit

**Task ID**: T016  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-04  
**Assignee**: TBD  
**Sprint**: Phase 3 — Analytics Engine  

### Description
Build the service for updating the three time-tracking fields (`estimated_time`, `remaining_time`, `actual_time`) on issues, with full audit trail logging in `time_logs`. Add `IssueController` for issue listing with filtering and `ProjectController` for project listing/detail endpoints.

### Acceptance Criteria
- [ ] `TimeTrackingService::updateTime(int $issueId, int $changedBy, array $fields): void` accepts a partial map of `{estimated_time?, remaining_time?, actual_time?}` — only provided keys are updated
- [ ] Input validation: each provided value must be a non-negative number in range [0, 9999.99]; throws `\InvalidArgumentException` with field name and value on violation
- [ ] Reads current values from `issues` table before writing; for each provided field, inserts a `time_logs` row with `old_value`, `new_value`, `changed_by`, `field_name`
- [ ] Updates `issues` table with new time values and `updated_at = NOW()`; entire read + log + update wrapped in a single PDO transaction
- [ ] `IssueController::getIssues(Request, Response, array): Response` implements `GET /api/projects/{id}/issues` with optional query params `?assignee=X`, `?iteration=X`, `?status=open|closed`; returns paginated JSON `{issues: [...], total: int}`
- [ ] `IssueController::updateTime(Request, Response, array): Response` implements `PUT /api/issues/{id}/time`; calls `TimeTrackingService::updateTime()`; returns updated issue JSON on success; returns 400 with error message on validation failure; requires `AuthMiddleware` (member or admin)
- [ ] `ProjectController::listProjects(Request, Response, array): Response` implements `GET /api/projects`; returns all projects ordered by `last_synced_at DESC`
- [ ] `ProjectController::getProject(Request, Response, array): Response` implements `GET /api/projects/{id}`; returns project detail including `last_synced_at`, `open_count`, `closed_count` derived from `issues` table aggregate
- [ ] `TimeTrackingService`, `IssueController`, `ProjectController`, `TimeLogRepository` wired into `config/container.php`
- [ ] Routes for `GET /api/projects`, `GET /api/projects/{id}`, `GET /api/projects/{id}/issues`, `PUT /api/issues/{id}/time` connected in `config/routes.php`

### Tasks/Subtasks
- [ ] Create `src/Repositories/TimeLogRepository.php` — `insert(int $issueId, int $userId, string $fieldName, float $oldValue, float $newValue): void`; `findByIssue(int $issueId, int $limit = 50): array`; PDO prepared statements
- [ ] Create `src/Services/TimeTrackingService.php` — constructor injects `TimeLogRepository` and `PDO`; implement `updateTime()` with transaction, validation, read-before-write, multi-field log entries
- [ ] Create `src/Controllers/IssueController.php` — constructor injects `IssueRepository` and `TimeTrackingService`; implement `getIssues()` (with filter parsing from query params) and `updateTime()`
- [ ] Create `src/Controllers/ProjectController.php` — constructor injects `ProjectRepository`; implement `listProjects()` and `getProject()` with issue aggregate counts via `IssueRepository::getCountsByProject(int $projectId): array`
- [ ] Add `IssueRepository::findByProject(int $projectId, array $filters): array` — builds WHERE clause from optional `assignee`, `iteration`, `status` filter array; all conditions via PDO prepared statements with parameterized binding
- [ ] Add `IssueRepository::getCountsByProject(int $projectId): array` — returns `['open' => int, 'closed' => int]` for a project
- [ ] Wire all new classes into `config/container.php`; replace placeholder 501 responses in `config/routes.php` with real controller references
- [ ] Write unit test: `TimeTrackingServiceTest` — (a) partial update only logs/changes provided fields, (b) negative value throws `InvalidArgumentException`, (c) value > 9999.99 throws `InvalidArgumentException`, (d) transaction rollback on log insert failure

### Definition of Done
- [ ] All acceptance criteria met
- [ ] `PUT /api/issues/{id}/time` requires authentication (returns 401 if called without session)
- [ ] `time_logs` table grows by one row per changed field per call
- [ ] Transaction ensures no partial writes: either all `time_logs` rows and the `issues` update commit together or none do
- [ ] Validation error returns HTTP 400 with `{error: "message"}` JSON body
- [ ] All DB queries use PDO prepared statements

### Dependencies
- T002 — `issues`, `time_logs` tables (migrations 003, 004)
- T005 — `AuthMiddleware` and session-based authentication for `PUT /api/issues/{id}/time`
- T006 — Route definitions file; `config/container.php` established

### Effort Estimate
**Time Estimate**: 1 day

### Priority
High — Required by Phase 4 Issues View inline time editor (T023) and by T014/T015 analytics queries

### Labels/Tags
- Category: development
- Component: backend, time-tracking, audit, issues
- Sprint: Phase 3 — Analytics Engine

### Notes
- `PUT /api/issues/{id}/time` must be behind `AuthMiddleware` (any authenticated user), not `AdminMiddleware` — team members update their own time
- The `time_logs` table uses `ENUM('estimated_time','remaining_time','actual_time')` for `field_name` — validation must reject any other string
- The read-before-write approach for logging is intentional; race conditions are acceptable at this team size (6–15 users)
- `ProjectController::getProject()` returns `open_count`/`closed_count` derived live from `issues` — not cached
- Source Requirements: R-004, R-006

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-04
