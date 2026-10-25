# T043 — Issues API: Server-Side Pagination, Filters, Sort and Totals

**Task ID**: T043  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-ADD-001](../artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md)  

### Description
Add pagination to `GET /api/projects/{id}/issues` so large projects do not return every issue at once.

### Acceptance Criteria
- [x] Query params: `page` (default 1), `page_size` (10/25/50/100, default 25), `assignee`, `iteration`, `status`, `sort` (whitelist), `dir`
- [x] Response keeps `issues` and `total`; adds `meta { page, page_size, total, total_pages }`, `totals { estimated, remaining, actual }` over the whole filtered set, and `assignees` (distinct, for the filter dropdown); a page beyond the last is clamped to the last page
- [x] Invalid `page`, `page_size`, `sort` or `dir` → 400; sort columns are whitelisted, all values bound — no SQL concatenation of user input
- [x] Without params: first page, default size 25 (default order unchanged: `github_updated_at` DESC)
- [x] Secondary sort on `id` keeps paging stable; no new index needed (existing `project_id`/`status`/`assignee` indexes); 3–6 ms with 1,060 issues locally
- [x] PHPUnit (paging boundaries, clamping, defaults, filters across pages, stability, 400 cases, totals) and smoke script updated

### Tasks/Subtasks
- [x] `IssueRepository::findPageByProject()/summarizeByProject()/distinctAssignees()` with a shared filter builder
- [x] `IssueController::getIssues()` validation and response shape
- [x] Tests and `api-smoke.sh`

### Definition of Done
- [x] PHPUnit and smoke pass  

### Dependencies
- None  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
Medium  

### Progress Updates
- **2026-10-24**: Implemented.

---
**Status**: Completed  
**Last Updated**: 2026-10-24
