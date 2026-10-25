# T044 — Issues View: Pagination Controls and Page-Size Selector

**Task ID**: T044  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: Copilot  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-ADD-001](../artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md)  

### Description
Add a reusable pagination bar with a page-size dropdown to the Issues view and move filtering/sorting to the server-side parameters from T043.

### Acceptance Criteria
- [x] New `PaginationBar.vue`: page-size dropdown (10/25/50/100), prev/next and page numbers with ellipsis, "Showing x–y of N"
- [x] `projectStore` sends `page`, `page_size`, filters and sort to the API and stores `issuesMeta` / `issuesTotals` / `assignees`; latest request wins
- [x] Page size persisted in `localStorage` (default 25); page resets to 1 on filter, sort or page-size change
- [x] 60 s polling keeps the current page; the server clamps a page that no longer exists
- [x] Totals row is labelled "Totals (all N matching issues)" and uses the API totals
- [x] Assignee options come from the API (`assignees`), sprint options from the iterations endpoint — independent of the current page
- [x] Hours are read-only text; no duplicate requests on page load
- [x] Browser-verified on the local dev server (production check is part of T045)

### Tasks/Subtasks
- [x] `PaginationBar.vue`, `projectStore.js` rewrite, `IssuesView.vue` rewrite

### Definition of Done
- [x] `npm run build` clean; manual checklist recorded in `tests/e2e/FINDINGS.md`  

### Dependencies
- T043 (API); T042 (sprint filter source)  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
Medium  

### Progress Updates
- **2026-10-24**: Implemented and verified locally with 60 issues (25 default rows, 10/25/50/100, paging, filter and sort across pages, size remembered after reload).

---
**Status**: Completed  
**Last Updated**: 2026-10-24
