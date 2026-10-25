# T041 — Iterations Endpoint and Current Iteration in Sync

**Task ID**: T041  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-002](../artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md)  

### Description
Provide the list of iteration labels for a project and keep `projects.current_iteration` up to date during sync. The Dashboard currently shows only "Latest" because no API supplies iterations.

### Acceptance Criteria
- [ ] `GET /api/projects/{id}/iterations` (auth required, 404 for unknown project) returns `{ "iterations": [ { "name", "start_date", "duration_days", "is_current", "has_data" } ] }`, newest first
- [ ] Source: union of `issues.iteration`, `burndown_daily.iteration` and GitHub iteration configuration (active and completed)
- [ ] `FETCH_PROJECT_FIELDS` query requests `completedIterations` in addition to `iterations`; parser exposes start date/duration
- [ ] `SyncService` sets `projects.current_iteration` to the iteration containing today (fallback: latest iteration with data)
- [ ] Iteration field name comes from `REPO_FIELD_ITERATION` (default `Iteration`, shared with T037)
- [ ] PHPUnit + smoke script cover the endpoint; response < 200 ms

### Tasks/Subtasks
- [ ] Repository methods (distinct iterations from issues/burndown)
- [ ] Controller + route + container wiring
- [ ] Query/parser changes + `ProjectRepository::updateCurrentIteration()`
- [ ] Tests (including the empty-project case)

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit passes  

### Dependencies
- T037 (field-name setting) — may proceed in parallel if the default name is used  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Labels/Tags
- Category: backend, api  
- Component: ProjectController, ProjectRepository, GraphQL  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
