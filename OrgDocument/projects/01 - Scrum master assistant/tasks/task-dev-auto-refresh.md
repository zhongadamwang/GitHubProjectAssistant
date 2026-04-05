# T027 — Implement Auto-Refresh & Polling

**Task ID**: T027  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Add background polling to the Dashboard and Issues views. The Dashboard burndown chart refreshes every 30 seconds; the Issues view refreshes every 60 seconds and also immediately after a successful time save. All timers are cleaned up when the user navigates away to prevent memory leaks.

### Acceptance Criteria
- [ ] Dashboard burndown chart auto-refreshes every 30 seconds via `setInterval` in `dashboardStore`
- [ ] Issues view auto-refreshes its issue list every 60 seconds
- [ ] Issues view triggers an immediate refresh after `IssueTimeEditor` emits a successful save
- [ ] Both polling timers are cleared in the respective view's `onUnmounted` lifecycle hook
- [ ] A subtle loading spinner or pulsing indicator is visible during each background refresh (does NOT block UI)
- [ ] Polling does NOT fire if the user is no longer on the relevant view (guard via `onUnmounted` cleanup)
- [ ] No duplicate requests: if a previous poll fetch is still in-flight when the next interval fires, skip the new request

### Tasks/Subtasks
- [ ] Update `frontend/src/stores/dashboardStore.js` — add `startPolling(intervalMs)` and `stopPolling()` methods using `setInterval`; track `pollingTimer` ref; guard against in-flight duplicates via `loading` flag
- [ ] Update `frontend/src/views/DashboardView.vue` — call `dashboardStore.startPolling(30000)` in `onMounted`; call `dashboardStore.stopPolling()` in `onUnmounted`
- [ ] Update `frontend/src/stores/projectStore.js` — add `startPolling(intervalMs)` / `stopPolling()` with same pattern (60-second default)
- [ ] Update `frontend/src/views/IssuesView.vue` — mount/unmount polling; listen for `saved` event from `IssueTimeEditor` to trigger immediate `fetchIssues()`
- [ ] Update `frontend/src/components/IssueTimeEditor.vue` — emit `saved` event on successful API response

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Dashboard network tab shows a `GET /api/projects/{id}/burndown` request every ~30 seconds
- [ ] Navigating to a different route stops the polling (no further requests visible in network tab)
- [ ] In-flight guard: opening DevTools and throttling to Slow 3G does not produce duplicate in-flight requests

### Dependencies
- T022 — Dashboard View (dashboardStore.js must exist)
- T023 — Issues View (projectStore.js must exist with `fetchIssues`)

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
Medium — Enhances data freshness; not blocking for initial release but required per acceptance criteria

### Labels/Tags
- Category: development
- Component: frontend, polling, stores
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Use `setInterval` + `clearInterval` — do NOT use `setTimeout` recursion for simplicity
- The in-flight guard should check `loading` state before calling `fetchBurndown()` / `fetchIssues()`
- Source Requirements: R-006

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
