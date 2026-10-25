# T042 — Sprint Selector Wiring (Dashboard, Issues, Members)

**Task ID**: T042  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-002](../artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md)  

### Description
Populate the Dashboard sprint dropdown from the iterations endpoint, default it to the current iteration, and make it project-scoped. Reuse the same source for the Issues and Members sprint filters.

### Acceptance Criteria
- [ ] `api.getIterations(projectId)` added; `projectStore` (or new store) holds iterations per project
- [ ] Dashboard `SprintSelector` lists all iteration labels of the selected project; current iteration preselected (fallback "Latest")
- [ ] Changing the project reloads iterations and resets the selected sprint and chart
- [ ] Selecting a sprint loads its burndown; empty sprint shows the existing "No burndown data" message
- [ ] Issues and Members sprint filters use the same iteration list (not derived from loaded issues)
- [ ] Removes the dead `points[].iteration` derivation in `DashboardView`

### Tasks/Subtasks
- [ ] API service + store
- [ ] `DashboardView`, `IssuesView`, `MembersView` updates
- [ ] Manual browser verification (local dev server) and screenshot evidence in `tests/e2e/FINDINGS.md`

### Definition of Done
- [ ] All acceptance criteria met; `npm run build` clean  

### Dependencies
- T041  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High  

### Labels/Tags
- Category: frontend  
- Component: DashboardView, SprintSelector, stores  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
