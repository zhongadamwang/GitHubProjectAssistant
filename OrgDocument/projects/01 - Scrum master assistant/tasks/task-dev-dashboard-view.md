# T022 — Build Dashboard View with Burndown Chart

**Task ID**: T022  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Build the main dashboard page. It shows a sprint selector dropdown and a Chart.js line chart comparing the ideal burndown curve (dashed blue) against the actual remaining hours (solid red). A sprint health badge tells users at a glance whether the sprint is on track.

### Acceptance Criteria
- [ ] `DashboardView.vue` is the default authenticated route `/`; displays project selector, sprint selector, burndown chart, and health indicator
- [ ] `SprintSelector.vue` — dropdown populated from distinct `iteration` values; defaults to the most recent iteration returned by API
- [ ] `BurndownChart.vue` — Chart.js line chart with two datasets:
  - Dataset 1 "Ideal": dashed blue line
  - Dataset 2 "Actual": solid red line
  - X-axis: dates (formatted `MMM D`); Y-axis: hours remaining
- [ ] Sprint health indicator badge below chart, computed from last data point:
  - "On Track" (green) — actual ≤ ideal
  - "At Risk" (yellow) — actual > ideal by < 20 %
  - "Behind" (red) — actual > ideal by ≥ 20 %
- [ ] `dashboardStore` (Pinia) — `fetchBurndown(projectId, iteration)` action; state: `points`, `iteration`, `loading`, `error`
- [ ] Auto-refresh every 30 seconds (handled in T027 but `dashboardStore` must expose a `refresh()` method)
- [ ] Chart is responsive (`maintainAspectRatio: false` with a parent container that constrains height)
- [ ] Empty state shown when no burndown data is available yet

### Tasks/Subtasks
- [ ] Create `frontend/src/stores/dashboardStore.js` — `fetchBurndown()`, `refresh()`, state slice
- [ ] Create `frontend/src/components/BurndownChart.vue` — receives `points` prop; builds Chart.js config; uses `onMounted`/`onUnmounted` to init and destroy chart instance
- [ ] Create `frontend/src/components/SprintSelector.vue` — `modelValue` + `update:modelValue` for v-model compatibility; emits selected iteration
- [ ] Create `frontend/src/components/HealthBadge.vue` — computes health status from last actual vs ideal point; renders colored badge
- [ ] Create `frontend/src/views/DashboardView.vue` — composes above components, calls `dashboardStore.fetchBurndown()` on mount, responds to sprint selection changes
- [ ] Register `/` route in `frontend/src/router/index.js` pointing to `DashboardView`; mark `requiresAuth: true`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Chart renders correct curves for a seeded dataset (verify visually in dev)
- [ ] Health badge changes correctly across all three threshold conditions
- [ ] Switching sprint in the dropdown fetches new data and re-renders chart
- [ ] Navigating away and back does not leave orphaned Chart.js canvas instances

### Dependencies
- T021 — API service layer (`getBurndown` method required)
- T020 — Auth store (route guard for `/`)

### Effort Estimate
**Time Estimate**: 1.5 days

### Priority
High — Core dashboard feature; required by T027 (auto-refresh)

### Labels/Tags
- Category: development
- Component: frontend, dashboard, chart, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Destroy the Chart.js instance in `onUnmounted` to prevent canvas reuse errors on hot-reload
- Chart height should be fixed at ~300px via the parent div, not the canvas element itself
- The `points` array shape: `[{ date: "YYYY-MM-DD", ideal: float, actual: float }, ...]`
- Source Requirements: R-005, R-006, R-007

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
