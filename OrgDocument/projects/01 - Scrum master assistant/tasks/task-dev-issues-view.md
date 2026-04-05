# T023 — Build Issues View with Time Editor

**Task ID**: T023  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Build the issues management page showing a sortable, filterable table of all project issues with inline editing for time fields (estimated, remaining, actual hours). Changes are saved immediately via `PUT /api/issues/{id}/time` on blur or Enter.

### Acceptance Criteria
- [ ] `IssuesView.vue` displays a table with columns: Issue #, Title, Assignee, Status, Iteration, Estimated (h), Remaining (h), Actual (h)
- [ ] Filter controls above the table:
  - Assignee dropdown (populated from unique assignees in data)
  - Iteration dropdown (populated from unique iterations)
  - Status toggle: All / Open / Closed
- [ ] Clicking a column header sorts that column ascending; clicking again reverses order; a sort indicator arrow is visible
- [ ] `IssueTimeEditor.vue` — renders three inline `<input type="number">` fields for estimated/remaining/actual; on blur or Enter calls `api.updateIssueTime(issueId, { estimated_hours, remaining_hours, actual_hours })`
- [ ] Save success: input briefly flashes green; save error: input briefly flashes red and reverts to previous value
- [ ] Footer row shows column totals for Estimated, Remaining, and Actual
- [ ] `projectStore` (Pinia) manages `issues` list; `fetchIssues(projectId)` action; `filterAssignee`, `filterIteration`, `filterStatus`, `sortKey`, `sortDir` state; `filteredIssues` computed getter
- [ ] Loading skeleton shown while initial fetch is in progress

### Tasks/Subtasks
- [ ] Create `frontend/src/stores/projectStore.js` — `fetchIssues()` action, filter/sort state, `filteredIssues` computed
- [ ] Create `frontend/src/components/IssueTimeEditor.vue` — three numeric inputs, v-model per field, blur/enter handler calling `api.updateIssueTime`; optimistic update pattern (revert on error)
- [ ] Create `frontend/src/views/IssuesView.vue` — filter controls, sortable table headers, `<IssueTimeEditor>` per row, footer totals row
- [ ] Register `/issues` route in `frontend/src/router/index.js`; mark `requiresAuth: true`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Filtering by assignee correctly narrows visible rows
- [ ] Sorting by "Estimated" column orders rows numerically
- [ ] Saving a time value updates the row without a full page reload
- [ ] Footer totals update immediately when a value is saved

### Dependencies
- T021 — API service layer (`getIssues`, `updateIssueTime` required)
- T020 — Auth store (route guard)

### Effort Estimate
**Time Estimate**: 1.5 days

### Priority
High — Core time-tracking UI feature; required by T027 (auto-refresh)

### Labels/Tags
- Category: development
- Component: frontend, issues, time-tracking, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Inline editing should NOT use a separate modal — edit-in-place for minimal friction
- The PUT body can include only the changed field(s) — backend ignores undefined fields
- Optimistic update: update the local store immediately, then revert if the API call fails
- Source Requirements: R-004, R-006

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
