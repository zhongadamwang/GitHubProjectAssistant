# T038 — Persist Synced Hours, Change Detection and Audit

**Task ID**: T038  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Store the hours parsed in T037 with **GitHub as the single source of truth** (owner decision), make sync detect changes made to GitHub *field values*, audit sync-originated changes, and make the dashboard read-only for hours.

### Acceptance Criteria
- [ ] `IssueRepository::upsertFromGitHub()` always writes `estimated_time/remaining_time/actual_time` from GitHub; an empty/cleared GitHub value is stored as `0.00`
- [ ] `SyncService` detects hour changes: change detection no longer relies only on Issue `updatedAt` — use the project item's `updatedAt` (`ProjectV2Item.updatedAt`) and/or compare mapped field values before deciding "unchanged"
- [ ] Sync result counts (`added/updated/unchanged`) remain correct; an issue whose only change is an hour value counts as `updated`
- [ ] Migration `009_*.sql` allows a system actor in `time_logs` (nullable `changed_by` or a `source` column) without breaking existing FK/PHPUnit tests; sync writes audit rows for hour changes
- [ ] **Dashboard is read-only for hours**: `IssueTimeEditor` replaced by plain text display in `IssuesView`; `PUT /api/issues/{id}/time` retired (route, controller method and `TimeTrackingService` user path removed or returning 405 — decide during implementation; keep `time_logs` for sync audit)
- [ ] Code comments/docs that state "local time fields are NEVER overwritten" are replaced by the new policy

### Tasks/Subtasks
- [ ] Simplify upsert SQL (hours always assigned from GitHub)
- [ ] Update `parseIssueNode()` to carry the item-level `updatedAt` used for diffing
- [ ] Add migration 009 and update `TimeLogRepository` for system actor
- [ ] Update `SyncService` diff logic + audit writes
- [ ] Frontend: remove `IssueTimeEditor` usage, show read-only hours; update `projectStore.saveIssueTime` (remove) and totals
- [ ] Tests: rewrite `SyncIntegrationTest::testLocalTimeFieldsPreservedOnResync` (encodes the old rule); remove/adjust the time-update E2E tests (`test_update_time_*`, `test_partial_update_*`) and the smoke script `PUT /issues/{id}/time` cases

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit + smoke pass  
- [ ] Migration applies cleanly on MySQL 5.7/8.0 (cPanel) and is idempotent  

### Dependencies
- T037  

### Effort Estimate
**Time Estimate**: 1.5 days (includes read-only UI and retiring the time-update endpoint)  

### Priority
High  

### Labels/Tags
- Category: backend, data  
- Component: SyncService, IssueRepository, migrations  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
