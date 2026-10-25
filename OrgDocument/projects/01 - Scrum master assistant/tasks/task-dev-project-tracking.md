# T050 — Project Tracking: Discover, Track/Untrack, Field Mapping (API + Admin UI)

**Task ID**: T050  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
Let admins pick which GitHub Projects of an account are tracked, configure per-project field names and validate them; expose tracking state through the Projects API.

### Acceptance Criteria
- [ ] Admin endpoints: track (`POST /api/admin/projects` with account + project number), update mapping/label (`PUT /api/admin/projects/{id}`), untrack (archive, data kept), re-track, delete data (explicit, confirmation token), `POST /api/admin/projects/{id}/validate-fields`
- [ ] `validate-fields` reads the project's fields from GitHub and reports for Estimate/Remaining/Actual/Iteration: found / missing / wrong type; saving a mapping with problems is allowed but flagged
- [ ] `GET /api/projects` returns `account` (label, owner), `is_tracked`, `archived`, `last_sync_status`, `last_sync_at`; archived projects excluded unless `?include_archived=1`
- [ ] Admin UI "Projects" panel: discover list per account (title, number, open/closed, tracked toggle), mapping editor with defaults shown as placeholders, validate button with per-field result, untrack/re-track/delete
- [ ] Tracking an already-tracked project is idempotent; the same GitHub project cannot be tracked twice
- [ ] Tracking more than 10 projects shows a soft warning (system limitation: ≤ 10 projects × ≤ ~1,000 issues) but is not blocked
- [ ] PHPUnit + smoke coverage; audit entries written; browser-verified

### Tasks/Subtasks
- [ ] `ProjectAdminController` + routes + repository methods
- [ ] Field validation service (reuses `FETCH_PROJECT_FIELDS`)
- [ ] `ProjectsAdminView.vue` / panel and API/store functions

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit and smoke pass; `npm run build` clean  

### Dependencies
- T046, T048  

### Effort Estimate
**Time Estimate**: 1.5 days  

### Priority
High  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
