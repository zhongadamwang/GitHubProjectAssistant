# Dashboard Sprint Selector Lists Project Iterations

## Change ID
**Change ID**: REQ-CHG-002  
**Date Created**: 2026-10-24  
**Status**: Implemented — pending production verification (2026-10-24)  
**Priority**: High  
**Requested By**: Project owner (live-site review, feedback #1)  
**Change Type**: REQ-CHG (corrects delivery of R-007 sprint health per iteration)

## Summary
The Dashboard sprint dropdown only offers "Latest"; it must list the iteration labels of the selected project.

## Change Details
### Current State
- `DashboardView` builds the option list from `dashStore.points[].iteration`, but `BurndownPoint` only carries `date`, `ideal`, `actual` — so the list is always empty and only "Latest" is shown.
- No API returns the list of iterations for a project. `GET /api/projects/{id}/burndown` resolves "latest" from `burndown_daily`.
- `projects.current_iteration` exists but the sync never populates it.
- The GitHub GraphQL query already requests the iteration field configuration (`iterations { id title startDate duration }`) and per-item iteration values, but only the item iteration title is used (`customFields['iteration']`, i.e. a field literally named "iteration").
- Changing the project dropdown does not refresh the sprint list (it is not project-scoped).
- The Issues and Members views derive their sprint filters from the loaded issue list, so they are inconsistent with the Dashboard.

### Proposed State
- New endpoint `GET /api/projects/{id}/iterations` returning iteration labels for the project (distinct from `issues.iteration` and `burndown_daily`, enriched with GitHub iteration config — start date, duration — and flags `is_current`, `has_data`), newest first.
- The sync stores the project's active iteration in `projects.current_iteration` (derived from the iteration field configuration: the iteration whose `startDate`…`startDate + duration` contains today).
- Dashboard sprint selector is populated from this endpoint, defaults to the current iteration, and reloads when the project changes. Issues and Members views reuse the same source.
- The GitHub iteration field name is configurable (`REPO_FIELD_ITERATION`, default `Iteration`) instead of the hard-coded lower-case `iteration` key.
- Note: GitHub returns upcoming/active iterations under `iterations` and finished ones under `completedIterations`; the query must request both so past sprints are selectable.

### Rationale
Sprint-level health (R-007) is only usable if users can pick which sprint to view and compare past sprints.

## Impact Analysis

| File / component | Impact | Update required |
|---|---|---|
| `src/Controllers/ProjectController.php` (or new `IterationController`), `config/routes.php`, `config/container.php` | New endpoint | Yes |
| `src/Repositories/IssueRepository.php`, `BurndownRepository.php`, `ProjectRepository.php` | Iteration listing queries; write `current_iteration` | Yes |
| `src/GraphQL/Queries.php`, `ResponseParser.php`, `SyncService.php` | Request `completedIterations`; derive current iteration; configurable field name | Yes |
| `frontend/src/services/api.js`, `stores/dashboardStore.js`, `views/DashboardView.vue`, `SprintSelector.vue`, Issues/Members views | Consume endpoint | Yes |
| Tests / smoke script | New endpoint coverage | Yes |

**Affected tasks**: T022 (Dashboard view), T013 (burndown API), T024 (Members view), T023 (Issues view).  
**Risk level**: Medium.  
**Estimated effort**: ~1.5 days (T041–T042).

## Decisions / Assumptions
1. **Iteration field name is `Iteration`** (owner, 2026-10-24).
2. Assumed (not yet confirmed): completed iterations are selectable, and iterations without data are listed but marked `has_data: false`.

## Implementation Plan
1. T041 — Backend iterations endpoint + current iteration in sync.
2. T042 — Frontend selector wiring and defaults.

## Acceptance Criteria
- Dashboard sprint dropdown lists all iteration labels of the selected project; the current iteration is preselected.
- Switching project refreshes the list and the chart.
- Selecting an iteration loads its burndown; an iteration without data shows the empty-state message.
- Issues and Members sprint filters show the same list.

## Approval Status
- [x] Owner confirmed iteration field name; assumption 2 stands unless the owner objects
- [x] Approved for implementation

## Related
- Tasks: [T041](../../tasks/task-dev-iterations-endpoint.md), [T042](../../tasks/task-dev-sprint-selector-frontend.md)
- Related change: [REQ-CHG-001](2026-10-24-REQ-CHG-001-sync-hours-from-github.md) (iteration field naming)
