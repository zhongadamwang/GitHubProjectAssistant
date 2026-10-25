# T042 — Sprint Selector Wiring (Dashboard, Issues, Members)

**Task ID**: T042  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-002](../artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md)  

### Description
Populate the Dashboard sprint dropdown from the iterations endpoint, default it to the current iteration, and make it project-scoped. Reuse the same source for the Issues and Members sprint filters.

### Acceptance Criteria
- [x] `api.getIterations(projectId)`; `projectStore` holds `iterations`, `iterationNames`, `currentIteration`
- [x] Dashboard `SprintSelector` lists all iteration labels; the current iteration is preselected and marked "(current)"; fallback "Latest"
- [x] Changing the project reloads iterations, resets the sprint, reloads the chart and restarts polling for the new project (previously polling stayed on the first project)
- [x] Selecting a sprint loads its burndown; a sprint without data shows "No burndown data available for this sprint."
- [x] Issues and Members sprint filters use the same iteration list
- [x] Dead `points[].iteration` derivation removed; burndown requests are "latest wins" (no dropped switches)

### Tasks/Subtasks
- [x] API service, store, `SprintSelector` (`current` prop), `DashboardView`, `IssuesView`, `MembersView`, `dashboardStore`
- [x] Browser verification on the local dev server (Sprint 4/3/2/1; current preselected; empty sprint)

### Definition of Done
- [x] `npm run build` clean; manual checklist recorded in `tests/e2e/FINDINGS.md`  

### Dependencies
- T041  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High  

### Progress Updates
- **2026-10-24**: Implemented and verified locally.

---
**Status**: Completed  
**Last Updated**: 2026-10-24
