# T043 — Issues API: Server-Side Pagination, Filters, Sort and Totals

**Task ID**: T043  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-ADD-001](../artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md)  

### Description
Add pagination to `GET /api/projects/{id}/issues` so large projects do not return every issue at once.

### Acceptance Criteria
- [ ] Query params: `page` (default 1), `page_size` (10/25/50/100, default 25), `assignee`, `iteration`, `status` (`open|closed`), `sort` (whitelist: `id,title,assignee,status,iteration,estimated_time,remaining_time,actual_time`), `dir` (`asc|desc`)
- [ ] Response: existing `issues` array plus `meta { page, page_size, total, total_pages }` and `totals { estimated, remaining, actual }` for the filtered set; out-of-range page returns the last page (or empty with correct meta — decide and document)
- [ ] Invalid `page_size`/`sort` are rejected with 400 or normalised to defaults (document the choice); all values bound / whitelisted — no SQL concatenation of user input
- [ ] Without params behaviour stays backward compatible (first page, default size) and the frontend is updated in T044
- [ ] `EXPLAIN` shows indexed access; add index if needed (`issues(project_id, status)` / `(project_id, assignee)`); response < 200 ms with 1,000 issues
- [ ] PHPUnit: paging boundaries, filters across pages, sort stability (secondary sort on `id`), totals; smoke script updated

### Tasks/Subtasks
- [ ] `IssueRepository::findPageByProject()` + `countByProject()` + `sumTimesByProject()`
- [ ] `IssueController` parameter parsing/validation
- [ ] Optional migration for indexes
- [ ] Tests and `api-smoke.sh` cases

### Definition of Done
- [ ] All acceptance criteria met; PHPUnit and smoke pass  

### Dependencies
- None (T044 consumes it)  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
Medium  

### Labels/Tags
- Category: backend, api, performance  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
