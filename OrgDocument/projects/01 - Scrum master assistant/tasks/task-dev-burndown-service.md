# T013 — Implement BurndownService

**Task ID**: T013  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-04  
**Assignee**: TBD  
**Sprint**: Phase 3 — Analytics Engine  

### Description
Build the service that calculates ideal and actual burndown curves for a given project iteration. The `BurndownService` reads from the `burndown_daily` table to produce `{date, ideal, actual}` data point arrays consumable by the Chart.js line chart on the frontend dashboard. Also add the `BurndownController` wiring the `GET /api/projects/{id}/burndown` endpoint.

### Acceptance Criteria
- [ ] `BurndownService::getBurndown(int $projectId, string $iteration): array` returns an array of `BurndownPoint` objects with `date`, `ideal`, and `actual` fields
- [ ] Ideal curve: linearly interpolates from `total_estimated` on sprint start date down to 0 on sprint end date across working days; derived from `burndown_daily` first- and last-row dates for the iteration
- [ ] Actual curve: sourced directly from `burndown_daily.actual_remaining` per `snapshot_date`; missing days are filled by carrying forward the previous value
- [ ] Edge cases handled without errors: no `burndown_daily` rows yet, sprint with all-zero estimates, query for non-existent iteration → returns empty array
- [ ] `BurndownController::getBurndown(Request, Response, array): Response` implements `GET /api/projects/{id}/burndown` with optional `?iteration=X` query parameter; when omitted uses the most recent iteration name found in `burndown_daily`
- [ ] Response format: `{"project_id": int, "iteration": string, "points": [{date, ideal, actual}, ...]}`
- [ ] `BurndownService` and `BurndownController` wired into `config/container.php`
- [ ] `config/routes.php` route for `GET /api/projects/{id}/burndown` connected to `BurndownController`

### Tasks/Subtasks
- [ ] Create `src/Models/BurndownPoint.php` — simple value object with `date` (string YYYY-MM-DD), `ideal` (float), `actual` (float) public properties and a constructor
- [ ] Create `src/Repositories/BurndownRepository.php` — `getPointsForIteration(int $projectId, string $iteration): array`, `getLatestIteration(int $projectId): ?string`; all queries via PDO prepared statements
- [ ] Create `src/Services/BurndownService.php` — constructor injects `BurndownRepository`; implement `getBurndown()` and `captureDaily()` (see T014)
- [ ] Create `src/Controllers/BurndownController.php` — constructor injects `BurndownService`; implement `getBurndown(Request $request, Response $response, array $args): Response`
- [ ] Wire `BurndownRepository`, `BurndownService`, `BurndownController` into `config/container.php`
- [ ] Connect `GET /api/projects/{id}/burndown` route in `config/routes.php`
- [ ] Write unit test: `BurndownServiceTest` covering (a) ideal curve linear interpolation, (b) empty-rows edge case, (c) mid-sprint actual carry-forward

### Definition of Done
- [ ] All acceptance criteria met
- [ ] `GET /api/projects/{id}/burndown` returns valid JSON with `points` array
- [ ] Ideal and actual curves are numerically correct for a known test dataset
- [ ] `captureDaily()` stub is in place for T014 integration
- [ ] All DB queries use PDO prepared statements

### Dependencies
- T002 — `burndown_daily` and `issues` tables must exist (migration 006)
- T004 — Slim 4 entry point and route registration infrastructure
- T006 — Route definitions file; `config/container.php` established

### Effort Estimate
**Time Estimate**: 1 day

### Priority
High — Required by Phase 4 Dashboard View (T022) and T014 daily snapshot job

### Labels/Tags
- Category: development
- Component: backend, analytics, burndown
- Sprint: Phase 3 — Analytics Engine

### Notes
- `BurndownPoint` should be a simple readonly value object — not a DB-backed model
- Ideal curve calculation must use the same `snapshot_date` range found in `burndown_daily`; do not hard-code sprint length
- The `captureDaily()` method is defined here (T013) but integrated into the sync hook in T014
- Source Requirements: R-005, R-006, R-007

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-04
