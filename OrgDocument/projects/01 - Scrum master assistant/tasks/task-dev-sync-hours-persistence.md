# T038 — Persist Synced Hours (GitHub Wins), Read-Only Dashboard, Audit

**Task ID**: T038  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Store the hours parsed in T037 with **GitHub as the single source of truth** (owner decision), make sync detect changes made to GitHub *field values*, audit sync-originated changes, and make the dashboard read-only for hours.

### Acceptance Criteria
- [x] `IssueRepository::upsertFromGitHub()` always writes `estimated_time/remaining_time/actual_time` from GitHub; an empty/cleared GitHub value is stored as `0.00`
- [x] `SyncService` detects hour changes without relying only on the Issue `updatedAt`: it compares the stored hours / iteration / status / assignee with GitHub's values
- [x] Sync result counts stay correct; an issue whose only change is an hour value counts as `updated`
- [x] Migration `009_time_logs_system_actor.sql` makes `time_logs.changed_by` nullable; sync writes audit rows (NULL actor) for hour changes of existing issues
- [x] **Dashboard is read-only for hours**: `IssueTimeEditor` removed (hours shown as text); `PUT /api/issues/{id}/time`, `IssueController::updateTime`, `TimeTrackingService` and its tests removed (route now returns 404)
- [x] Comments/docs saying "local time fields are NEVER overwritten" replaced by the new policy

### Tasks/Subtasks
- [x] Upsert always assigns hours (null → 0)
- [x] `SyncService::differsFromStored()` + `auditHourChanges()`; `TimeLogRepository::insert()` accepts a null user
- [x] Frontend: read-only hour columns; `saveIssueTime`/`updateIssueTime` removed
- [x] Tests: rewrote the old "local hours preserved" test; added GitHub-wins, audit, field-only change, unchanged-resync tests; removed time-update E2E tests; smoke script asserts the route is retired

### Definition of Done
- [x] PHPUnit and smoke script pass  
- [x] Migration 009 is a single `ALTER TABLE … MODIFY` (valid on MySQL 5.7/8.0; runs once via `migrations_log`; verified on MySQL 9.6)  

### Dependencies
- T037  

### Effort Estimate
**Time Estimate**: 1.5 days  

### Priority
High  

### Progress Updates
- **2026-10-24**: Implemented. Note: `time_logs` keeps its user FK for history; existing rows are untouched.

---
**Status**: Completed  
**Last Updated**: 2026-10-24
