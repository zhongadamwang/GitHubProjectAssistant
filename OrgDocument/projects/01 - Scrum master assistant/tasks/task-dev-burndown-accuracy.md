# T039 — Burndown Accuracy with Synced Hours (Baseline, Closed Issues, Rebuild)

**Task ID**: T039  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-CHG-001](../artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md)  

### Description
Make the burndown calculation robust once real hours flow in, and repair existing production snapshots that were captured while all hours were 0.

### Acceptance Criteria
- [ ] Ideal-line baseline uses the sprint's maximum (or first non-zero) `total_estimated`, not blindly the first snapshot row
- [ ] Closed issues contribute 0 remaining in `captureDaily()` aggregation (**owner decision**) and open issues contribute their remaining hours; closed issues still count toward `total_estimated`
- [ ] Issues with `iteration IS NULL` are handled explicitly (excluded with a visible note, or grouped under the project's current iteration) instead of silently dropped
- [ ] CLI `php cron/rebuild-burndown.php [--project=ID] [--iteration=NAME]` (or documented SQL) recomputes today's snapshot and removes/repairs all-zero historical rows
- [ ] Unit tests: zero-baseline first day then real data; closed issue handling; null iteration; single-day sprint

### Tasks/Subtasks
- [ ] Update `BurndownService::getBurndown()` baseline logic
- [ ] Update `IssueRepository::aggregateTimeByIteration()` (closed → 0 remaining; include counts)
- [ ] Decide/implement null-iteration handling
- [ ] Add rebuild script + usage in `DEPLOYMENT.md`
- [ ] Tests in `tests/Integration/Phase3`

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit passes  
- [ ] Verified against a production-like dataset (post-sync) that ideal ≥ actual-start and curves are non-flat  

### Dependencies
- T038  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Labels/Tags
- Category: backend, analytics  
- Component: BurndownService, IssueRepository  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
