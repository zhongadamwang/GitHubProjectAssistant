# T041 — Iterations Endpoint and Current Iteration in Sync

**Task ID**: T041  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-002](../artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md)  

### Description
Provide the list of iteration labels for a project and keep `projects.current_iteration` up to date during sync. The Dashboard showed only "Latest" because no API supplied iterations.

### Acceptance Criteria
- [x] `GET /api/projects/{id}/iterations` (auth required, 404 for an unknown project) returns `{ "iterations": [ { "name", "start_date", "duration_days", "is_current", "is_completed", "has_data" } ] }`, newest first
- [x] Source: union of `project_iterations` (GitHub configuration), `issues.iteration` and `burndown_daily.iteration`, de-duplicated
- [x] `FETCH_PROJECT_FIELDS` requests `completedIterations`; the parser exposes start date / duration and a `completed` flag
- [x] `SyncService` stores the iteration configuration (migration `010_create_project_iterations_table.sql`) and sets `projects.current_iteration` to the sprint containing today
- [x] Iteration field name comes from `REPO_FIELD_ITERATION` (default `Iteration`)
- [x] PHPUnit + smoke script cover the endpoint; ~2 ms locally

### Tasks/Subtasks
- [x] Migration 010, `ProjectRepository::syncIterations()/updateCurrentIteration()/findIterations()`
- [x] `ProjectController::getIterations()` + route
- [x] Query/parser/`SyncService::syncIterations()`
- [x] Tests (iteration config + current sprint, endpoint flags/order, issues-only names, 404)

### Definition of Done
- [x] PHPUnit passes  

### Dependencies
- T037 (field-name setting)  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Progress Updates
- **2026-10-24**: Implemented. The iteration sync is non-fatal (a failure logs a warning and the sync continues).

---
**Status**: Completed  
**Last Updated**: 2026-10-24
