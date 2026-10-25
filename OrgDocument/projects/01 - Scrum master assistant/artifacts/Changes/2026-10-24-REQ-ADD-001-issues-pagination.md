# Issues List Pagination with Page-Size Selector

## Change ID
**Change ID**: REQ-ADD-001  
**Date Created**: 2026-10-24  
**Status**: Verified — owner sign-off 2026-10-24  
**Priority**: Medium  
**Requested By**: Project owner (live-site review, feedback #2)  
**Change Type**: REQ-ADD (new requirement)

## Summary
The Issues view lists every issue on one page; add pagination with a user-selectable page size.

## Change Details
### Current State
- `GET /api/projects/{id}/issues` returns all issues of the project (optionally filtered by iteration/status in the repository).
- `IssuesView` renders every row; filtering (assignee, sprint, status), sorting and totals are computed client-side in `projectStore`.
- The view polls every 60 s and re-fetches everything after each time edit.

### Proposed State
- **Server-side pagination** on `GET /api/projects/{id}/issues`: query params `page` (default 1), `page_size` (allowed 10 / 25 / 50 / 100, default 25, max 100), plus server-side `assignee`, `iteration`, `status`, `sort`, `dir` (whitelisted columns). Response keeps `issues` and adds `meta { page, page_size, total, total_pages }` and `totals { estimated, remaining, actual }` for the filtered set. Params are optional so existing callers keep working.
- **Issues view**: page-size dropdown (10/25/50/100), previous/next and page numbers, "Showing x–y of N", page size remembered in `localStorage`, page resets to 1 when filters/sort change, current page preserved across 60 s polling and after an inline time edit.
- Totals row shows totals for the filtered result (not just the visible page) — label clarified.
- Assignee/sprint filter options must not depend on the loaded page (served from the iterations endpoint of REQ-CHG-002 and a distinct-assignees source).

Server-side is recommended over client-side paging because GitHub projects can reach hundreds of issues and the 200 ms API target (T033) should hold. **To be confirmed by the owner.**

### Rationale
Usability and performance with large issue lists.

## Impact Analysis

| File / component | Impact | Update required |
|---|---|---|
| `src/Controllers/IssueController.php`, `src/Repositories/IssueRepository.php` | Pagination, filters, sort, totals | Yes |
| `frontend/src/services/api.js`, `stores/projectStore.js`, `views/IssuesView.vue` | Paged fetch, controls, state | Yes |
| New `frontend/src/components/PaginationBar.vue` | Reusable pagination control | Yes |
| `tests/Integration/Phase6/EndToEndTest.php`, `tests/e2e/api-smoke.sh` | New cases | Yes |
| Indexes (`issues(project_id, status/assignee/iteration)`) | Verify with EXPLAIN | Maybe |

**Affected tasks**: T023 (Issues view), T027 (polling), T033 (performance target).  
**Risk level**: Low–Medium.  
**Estimated effort**: ~2 days (T043–T044).

## Owner Decisions (2026-10-24)
1. Server-side pagination with page sizes 10 / 25 / 50 / 100, default 25 — **approved**.

## Implementation Plan
1. T043 — Backend pagination/filter/sort/totals.
2. T044 — Frontend controls and state handling.

## Acceptance Criteria
- Issues view shows at most `page_size` rows; page-size dropdown changes it immediately and is remembered.
- Navigation controls work; "Showing x–y of N" is correct; out-of-range pages are clamped.
- Filters/sort apply across the whole result set, not only the current page.
- Inline time edit and polling keep the user on the current page.
- API p95 for page requests stays < 200 ms; invalid `page_size` returns 400 (or falls back to default — decide in T043).

## Approval Status
- [x] Owner confirmed approach and page sizes
- [x] Approved for implementation

## Related
- Tasks: [T043](../../tasks/task-dev-issues-pagination-api.md), [T044](../../tasks/task-dev-issues-pagination-ui.md)
- Related change: [REQ-CHG-002](2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md) (filter option source)
