# T044 — Issues View: Pagination Controls and Page-Size Selector

**Task ID**: T044  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 7 — Post-Release Feedback  
**Change Reference**: [REQ-ADD-001](../artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md)  

### Description
Add a reusable pagination bar with a page-size dropdown to the Issues view and move filtering/sorting to the server-side parameters from T043.

### Acceptance Criteria
- [ ] New `PaginationBar.vue`: page-size dropdown (10/25/50/100), first/prev/next/last and page numbers, "Showing x–y of N"
- [ ] `projectStore` sends `page`, `page_size`, filters and sort to the API and stores `meta` / `totals`
- [ ] Page size persisted in `localStorage` (default 25); page resets to 1 on filter, sort or page-size change
- [ ] 60 s polling keeps the current page; if the page becomes empty the view moves to the last page
- [ ] Totals row is labelled as totals for all filtered issues (from API `totals`)
- [ ] Assignee and sprint filter options come from sources independent of the current page (iterations endpoint from T041/T042 and a distinct-assignee list)
- [ ] Inline time editor removed — hours are read-only text (see T038); no `IssueTimeEditor` refetch handling needed
- [ ] Browser-verified on local dev server and production

### Tasks/Subtasks
- [ ] `PaginationBar.vue` + styles
- [ ] Store refactor (server-side filter/sort/page)
- [ ] `IssuesView` integration (coordinate with T038 which makes hours read-only)

### Definition of Done
- [ ] All acceptance criteria met; `npm run build` clean; manual checklist added to `tests/e2e/FINDINGS.md`  

### Dependencies
- T043 (API); T042 recommended for the sprint filter source  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
Medium  

### Labels/Tags
- Category: frontend  
- Component: IssuesView, projectStore, PaginationBar  

---
**Status**: Not Started  
**Last Updated**: 2026-10-24
