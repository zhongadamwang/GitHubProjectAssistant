# T039 — Burndown Accuracy with Synced Hours (Baseline, Closed Issues, Rebuild)

**Task ID**: T039  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Make the burndown robust once real hours flow in, and repair existing production snapshots that were captured while all hours were 0.

### Acceptance Criteria
- [x] Ideal-line baseline is the sprint's largest `total_estimated`, not the first snapshot row
- [x] Closed issues contribute 0 remaining in `aggregateTimeByIteration()` (owner decision); open issues contribute their remaining hours; closed issues still count toward `total_estimated`
- [x] Issues with `iteration IS NULL` are excluded from burndown by design (they belong to no sprint); documented in `IssueRepository::aggregateTimeByIteration()`
- [x] CLI `php cron/rebuild-burndown.php [--project=ID]` removes all-zero-estimate snapshots and recaptures today's snapshot; usage documented in `DEPLOYMENT.md`
- [x] Tests: zero-baseline first day (`BurndownServiceTest`), closed issue handling and null iteration (`SyncIntegrationTest`)

### Tasks/Subtasks
- [x] `BurndownService::getBurndown()` baseline = max `total_estimated`
- [x] `IssueRepository::aggregateTimeByIteration()` closed → 0 remaining
- [x] `BurndownRepository::deleteEmptySnapshots()` + `cron/rebuild-burndown.php`
- [x] Verified locally: rebuild removed the empty snapshot and captured today's

### Definition of Done
- [x] PHPUnit passes  
- [ ] Verified against the real production dataset after a real sync (T040/T045)  

### Dependencies
- T038  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Progress Updates
- **2026-10-24**: Implemented and verified on seeded data.

---
**Status**: Completed (production verification pending in T040/T045)  
**Last Updated**: 2026-10-24
