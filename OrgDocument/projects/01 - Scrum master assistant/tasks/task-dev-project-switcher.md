# T052 — Global Project Switcher (Dashboard, Issues, Members, Sync)

**Task ID**: T052  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
One project selector in the top navigation drives all project-scoped views, remembers the choice, and scopes the Sync view and "Sync Now" to the selected project. Today only the Dashboard has a selector and the others silently use the first project.

### Acceptance Criteria
- [ ] Project dropdown in `NavBar` (shown when more than one active project; a single project is displayed as a label) populated from `GET /api/projects`
- [ ] Selection stored in `localStorage` and restored on reload; falls back to the first active project if the stored one is gone/archived; optional "Show archived" toggle for admins
- [ ] Dashboard, Issues, Members and Sync react to a change without a page reload: stores reset filters/page/iterations, reload data and restart polling for the new project (no stale responses from the previous project)
- [ ] Sync view shows the selected project's status/history (`?project_id=`) and "Sync Now" calls `POST /api/projects/{id}/sync`; status shows account problems ("token invalid — ask an admin") for non-admins and a link to settings for admins
- [ ] Empty state when no project is tracked: admins get a "Connect GitHub" call to action, others a short message
- [ ] The per-view project dropdown on the Dashboard is removed (single source of truth)
- [ ] Browser-verified with two projects including rapid switching and reload

### Tasks/Subtasks
- [ ] `projectStore` active-project handling + persistence; `NavBar` selector
- [ ] Update the four views and their stores; `SyncView` per-project
- [ ] Manual checklist in `tests/e2e/FINDINGS.md`

### Definition of Done
- [ ] All acceptance criteria met; `npm run build` clean  

### Dependencies
- T050 (project fields in the API); T051 for the per-project sync endpoint  

### Effort Estimate
**Time Estimate**: 1.5 days  

### Priority
High  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
