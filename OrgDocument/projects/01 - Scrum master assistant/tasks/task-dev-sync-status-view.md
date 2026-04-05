# T025 — Build Sync Status View

**Task ID**: T025  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Build the sync monitoring page. It shows the timestamp and result of the last GitHub sync, a history table of recent sync runs, and (for admins) a "Sync Now" button that triggers an immediate sync via `POST /api/sync/trigger`.

### Acceptance Criteria
- [ ] `SyncView.vue` displays: last sync timestamp, overall sync status badge, GraphQL points used in the last sync
- [ ] `SyncStatus.vue` status indicator:
  - Green — last sync within 30 minutes
  - Yellow — last sync more than 30 minutes ago
  - Red — last sync status = `failed`
- [ ] Sync history table columns: Date/Time, Issues Added, Issues Updated, Issues Removed, Points Used, Status
- [ ] "Sync Now" button visible only to users where `authStore.isAdmin === true`
- [ ] Clicking "Sync Now": button shows loading spinner; on completion shows success toast or inline error
- [ ] History table shows up to 20 most recent entries; sorted newest-first

### Tasks/Subtasks
- [ ] Create `frontend/src/components/SyncStatus.vue` — computed color class based on `lastSyncAt` ISO string and `lastStatus`; emits no events (display-only)
- [ ] Create `frontend/src/views/SyncView.vue` — fetches `getSyncHistory()` on mount; renders `<SyncStatus>`, summary stats, and history table; conditionally shows "Sync Now" button for admin
- [ ] Add `triggerSync()` action handler in `SyncView` — calls `api.triggerSync()`, awaits, refreshes history data
- [ ] Register `/sync` route in `frontend/src/router/index.js`; mark `requiresAuth: true`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Status indicator correctly computes color for all three conditions
- [ ] Admin can trigger a sync and sees the new history entry appear after completion
- [ ] Non-admin users do not see the "Sync Now" button

### Dependencies
- T021 — API service layer (`getSyncHistory`, `triggerSync` required)
- T020 — Auth store (`isAdmin` getter required)

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
Medium — Operational visibility; required for admin monitoring

### Labels/Tags
- Category: development
- Component: frontend, sync, admin, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- `triggerSync()` API call may take several seconds — set a generous timeout or stream progress if needed in v2
- Status color should use CSS classes, not inline styles, to allow future theming
- Source Requirements: R-001, R-003

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
