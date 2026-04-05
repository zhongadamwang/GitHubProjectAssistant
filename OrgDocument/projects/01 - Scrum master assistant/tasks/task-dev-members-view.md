# T024 — Build Members View with Efficiency Charts

**Task ID**: T024  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Build the member efficiency analysis page. It shows a grouped bar chart comparing estimated vs actual hours per team member and an accuracy table with color-coded ratio indicators.

### Acceptance Criteria
- [ ] `MembersView.vue` displays an `EfficiencyChart` and a summary accuracy table below it
- [ ] `EfficiencyChart.vue` renders a Chart.js grouped bar chart:
  - Group bars side-by-side per member login
  - Blue bars = Estimated hours; Orange bars = Actual hours
  - X-axis: member names; Y-axis: hours
- [ ] Accuracy summary table columns: Member, Total Estimated (h), Total Actual (h), Accuracy Ratio, Issues Completed
- [ ] Accuracy Ratio color coding:
  - 0.9 – 1.1 → green ("Accurate")
  - < 0.9 → blue ("Overestimated")
  - > 1.1 → red ("Underestimated")
- [ ] Iteration filter dropdown: "All Time" + individual iterations; triggers re-fetch with `?iteration=` param
- [ ] Loading skeleton shown while fetch is in progress
- [ ] Empty state shown if no efficiency data exists for the selected filter

### Tasks/Subtasks
- [ ] Create `frontend/src/components/EfficiencyChart.vue` — accepts `members` prop (array of `{login, estimated, actual}`); builds Chart.js grouped bar config; destroys chart in `onUnmounted`
- [ ] Create `frontend/src/views/MembersView.vue` — iteration filter, `<EfficiencyChart>`, accuracy table with computed ratio and color class
- [ ] Add `fetchMembers(projectId, iteration)` action to `projectStore.js` (created in T023); or create a separate `membersStore.js` if complexity warrants
- [ ] Register `/members` route in `frontend/src/router/index.js`; mark `requiresAuth: true`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Chart renders two grouped bars per member for a seeded dataset
- [ ] Choosing a specific iteration re-fetches and updates both chart and table
- [ ] Accuracy ratio color codes match the defined thresholds

### Dependencies
- T021 — API service layer (`getMembers` required)
- T020 — Auth store (route guard)

### Effort Estimate
**Time Estimate**: 1 day

### Priority
High — Efficiency analysis is a key product differentiator (R-008)

### Labels/Tags
- Category: development
- Component: frontend, members, efficiency, chart, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Accuracy Ratio = `actual / estimated`; guard against divide-by-zero (show "N/A" when estimated = 0)
- Chart height: ~280px parent div; `maintainAspectRatio: false`
- Source Requirements: R-008

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
