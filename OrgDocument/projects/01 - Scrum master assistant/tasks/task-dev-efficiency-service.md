# T015 — Implement EfficiencyService

**Task ID**: T015  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-04  
**Assignee**: TBD  
**Sprint**: Phase 3 — Analytics Engine  

### Description
Build per-member efficiency analysis: aggregate estimated vs actual time per assignee, compute an accuracy ratio, and surface historical trend data per sprint. Add `MemberController` wiring the `GET /api/projects/{id}/members` endpoint consumed by the Vue frontend Members view (T024).

### Acceptance Criteria
- [x] `EfficiencyService::getMemberEfficiency(int $projectId, ?string $iteration = null): array` returns an array of member efficiency records; when `$iteration` is `null`, aggregates across all iterations
- [x] Each record contains: `member` (assignee login string), `estimated` (sum of `estimated_time` for closed issues), `actual` (sum of `actual_time` for closed issues), `ratio` (actual/estimated, null when estimated = 0), `issues_count` (count of closed issues assigned to member)
- [x] `EfficiencyService::getMemberTrend(int $projectId, string $member): array` returns per-sprint accuracy ratio history: `[{iteration, estimated, actual, ratio, issues_count}, ...]` ordered by iteration ascending
- [x] `MemberController::getMembers(Request $request, Response $response, array $args): Response` implements `GET /api/projects/{id}/members?iteration=X`; iteration is optional; returns `{"project_id": int, "iteration": string|null, "members": [...], "trend": {}}` — trend map keyed by member login
- [x] Members with no closed issues in the requested scope are omitted from results (not zero-padded)
- [x] `EfficiencyService` and `MemberController` wired into `config/container.php`
- [x] `config/routes.php` route for `GET /api/projects/{id}/members` connected to `MemberController`

### Tasks/Subtasks
- [x] Add `IssueRepository::aggregateEfficiencyByMember(int $projectId, ?string $iteration): array` — `GROUP BY assignee` on closed issues with `SUM(estimated_time)`, `SUM(actual_time)`, `COUNT(*)`; optional `WHERE iteration = ?` filter; PDO prepared statements
- [x] Add `IssueRepository::aggregateEfficiencyByMemberAndIteration(int $projectId): array` — same aggregation but `GROUP BY assignee, iteration` for trend data
- [x] Create `src/Services/EfficiencyService.php` — constructor injects `IssueRepository`; implement `getMemberEfficiency()` and `getMemberTrend()`; ratio calculation: `$actual > 0 && $estimated > 0 ? round($actual / $estimated, 4) : null`
- [x] Create `src/Controllers/MemberController.php` — constructor injects `EfficiencyService`; implement `getMembers()` building the combined response with `members` array and `trend` map
- [x] Wire `EfficiencyService` and `MemberController` into `config/container.php`
- [x] Connect `GET /api/projects/{id}/members` route in `config/routes.php`
- [x] Write unit test: `EfficiencyServiceTest` covering (a) ratio = 0 when no closed issues, (b) ratio = null when estimated = 0 but actual > 0, (c) iteration filter correctly scopes results

### Definition of Done
- [x] All acceptance criteria met
- [x] `GET /api/projects/{id}/members` returns valid JSON with `members` array
- [x] Accuracy ratio is numerically correct for a known test dataset
- [x] Iteration filter reduces result set correctly
- [x] All DB queries use PDO prepared statements

### Dependencies
- T002 — `issues` table must exist with `assignee`, `estimated_time`, `actual_time`, `status`, `iteration` columns (migration 003)
- T004 — Slim 4 entry point and route registration infrastructure
- T006 — Route definitions file; `config/container.php` established

### Effort Estimate
**Time Estimate**: 1 day

### Priority
High — Required by Phase 4 Members View (T024)

### Labels/Tags
- Category: development
- Component: backend, analytics, efficiency
- Sprint: Phase 3 — Analytics Engine

### Notes
- Only **closed** issues should contribute to efficiency metrics; open issues may have incomplete `actual_time`
- `ratio > 1.0` means the member underestimated (took longer); `ratio < 1.0` means overestimated (finished early)
- `getMemberTrend()` result order should be alphabetical by iteration name (sprint names are typically alphanumeric e.g. "Sprint 1", "Sprint 2")
- Source Requirements: R-008

### Progress Updates
- **2026-04-04**: Added `IssueRepository::aggregateEfficiencyByMember()` (GROUP BY assignee on closed issues, COALESCE for NULLs, optional iteration WHERE clause, PDO prepared statements) and `aggregateEfficiencyByMemberAndIteration()` (GROUP BY assignee+iteration for trend data, ORDER BY assignee ASC, iteration ASC). Created `src/Services/EfficiencyService.php` — `getMemberEfficiency()` maps aggregate rows to typed records with null-safe ratio; `getMemberTrend()` filters all-rows result by member login. Created `src/Controllers/MemberController.php` — `getMembers()` calls both service methods and emits JSON with `members` array and `trend` keyed-object. Wired `EfficiencyService` and `MemberController` into `config/container.php`; replaced placeholder `members` route in `config/routes.php`. Created `tests/Integration/Phase3/EfficiencyServiceTest.php` — 9 tests covering: correct ratio math, null when estimated=0, null when actual=0, iteration filter forwarded, null iteration forwarded, trend filtering by member, trend empty for unknown member, empty project, multiple members with independent ratios.

---
**Status**: Completed
**Last Updated**: 2026-04-04
