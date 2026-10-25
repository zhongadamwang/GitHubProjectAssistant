# Multiple GitHub Projects with User Project Selection

## Change ID
**Change ID**: REQ-ADD-003  
**Date Created**: 2026-10-24  
**Status**: Approved (owner decisions received 2026-10-25)  
**Priority**: High  
**Requested By**: Project owner (2026-10-24: "Add multiple project support to allow user to select the GitHub project")  
**Change Type**: SCOPE-CHG / REQ-ADD (the system moves from one configured project to many; modifies R-001, R-002)

## Summary
Admins choose which GitHub Projects to track (across configured accounts); every user can select the project they want to view.

## Change Details

### Current State
- One project only: `REPO_ORG` + `REPO_PROJECT_NUMBER` in `.env`; `SyncService` is built with one `owner`/`projectNumber`, `cron/sync.php` and `POST /api/sync/trigger` sync exactly that project.
- The database is already project-aware (`projects`, `issues.project_id`, `burndown_daily.project_id`, `sync_history.project_id`, `project_iterations`) and most read endpoints take `/projects/{id}/…`.
- UI: only the Dashboard has a project dropdown. Issues, Members and Sync use `activeProjectId` (the first project) with no selector; the choice is not remembered.
- Hard-coded details that break with several projects: snapshot files are named `Y-m-d_H-i.json` in one folder (two projects in the same minute overwrite each other); the Sync view/history is not project-scoped; the hour/iteration field names are global settings although different projects may name them differently; queries are organization-only.
- A web-triggered sync runs inside one PHP request (shared hosting `max_execution_time` ≈ 30 s), which cannot safely sync many projects.

### Proposed State (requirements)
**Functional**
- **FR-P1** **Discover**: for a configured account, an admin can list the GitHub Projects (v2) it can see (number, title, open/closed, URL) and mark projects as *tracked*.
- **FR-P2** **Track / untrack**: tracking creates/activates the local project; untracking stops syncing but **keeps** historical data (project shown as archived). A separate explicit "delete project data" action (admin, confirmation) removes it.
- **FR-P3** **Per-project field mapping**: each tracked project can override the GitHub field names for Estimate / Remaining / Actual hours and Iteration (defaults: `Estimate Hours`, `Remains Hours`, `Actual Hours`, `Iteration`), with a "validate fields" check that reports missing or wrongly typed fields.
- **FR-P4** **Sync orchestration**: the cron job syncs every tracked project sequentially; one project's failure does not stop the others; each run writes `sync_history` per project. Snapshot files are stored per project (`data/snapshots/{project_id}/…`) with retention per project.
- **FR-P5** **Manual sync**: admins can trigger a sync for one project (`POST /api/projects/{id}/sync`); "Sync all" is performed by the cron/CLI or by the UI calling projects one after another so no single request exceeds the hosting time limit.
- **FR-P6** **Project selection (all users)**: a project switcher in the top navigation applies to Dashboard, Issues, Members and Sync; the selection is remembered per browser (localStorage) and falls back to the first active project; archived projects are hidden unless "show archived" is on.
- **FR-P7** **Per-project views**: Sync status/history and "Sync Now" are scoped to the selected project; the Projects API returns sync status, tracked/archived flags and the owning account.
- **FR-P8** A project's token comes from its account (REQ-ADD-002); if the account's token is invalid the project shows a clear "needs attention" state rather than failing silently.
- **FR-P9** Generic owner support: projects of organizations **and** user accounts (`repositoryOwner` query; verify in T048/T051).

**Non-functional**
- **NFR-P1** Respect GitHub rate limits (5,000 points/hour per token): the orchestrator checks remaining quota between projects and stops cleanly with a recorded `rate_limited` status.
- **NFR-P2** A full cron run for N projects must stay within the host's CLI limits (sequential, memory-bounded paging already in place); target N ≤ 10 with ≤ 1,000 issues each.
- **NFR-P3** API response times stay < 200 ms; list endpoints remain index-backed (`project_id`).
- **NFR-P4** Backward compatible: the existing project and its data are preserved and become the first tracked project.

### Out of scope (future)
- Per-project access control (all authenticated users see all tracked projects).
- Cross-project/portfolio dashboards.
- Webhooks for near-real-time sync.

### Data model (sketch)
`projects` gets `account_id` (FK → `github_accounts`), `is_tracked` (bool), `archived_at`, `field_mapping` (JSON, nullable = defaults), `last_sync_status`, `last_sync_at`; `github_owner/project_number` already exist. Existing row is linked to the imported account during the T053 migration.

### API (sketch)
`GET /api/admin/github-accounts/{id}/projects` (discover) · `POST/PUT/DELETE /api/admin/projects/{id}` (track, mapping, untrack) · `POST /api/admin/projects/{id}/validate-fields` · `POST /api/projects/{id}/sync` (admin) · `GET /api/projects` (adds `account`, `is_tracked`, `archived`, `last_sync_*`).

### Rationale
Teams usually run several projects (per team/product); a single `.env` project forces redeployments and hides everything else.

## Impact Analysis

| File / component | Impact | Update required |
|---|---|---|
| `database/migrations/012_*`, `ProjectRepository` | Tracking columns, mapping, status | Yes |
| `SyncService`, new `SyncOrchestrator` / `SyncServiceFactory`, `cron/sync.php`, `SyncController` | Per-project sync, loop, isolation, rate-limit guard, per-project trigger | Yes |
| `GitHubGraphQLService`, `Queries.php` | Account-owned client; `repositoryOwner`; project discovery query | Yes |
| Snapshot writing / retention | Per-project folders | Yes |
| `SyncHistoryRepository`, `SyncController::history` | Already supports `project_id`; UI must use it | Yes |
| `NavBar.vue`, `projectStore.js`, Dashboard/Issues/Members/Sync views | Global project switcher, persistence | Yes |
| New admin "Projects" settings UI | Discover / track / mapping / validate | Yes |
| Tests, smoke script, docs, `DEPLOYMENT.md` | Multi-project coverage and runbook | Yes |

**Affected tasks**: T010/T011 (sync/cron), T013/T022–T025 (views), T028 (workflow secrets).  
**Risk level**: Medium–High (core sync refactor, shared-hosting limits).  
**Estimated effort**: ~8 days (T050–T052 + parts of T053/T055).

## Owner Decisions (2026-10-25)
1. **Untracking archives the project and keeps its data** (explicit "delete data" action) — approved.
2. **Field mapping is per project** (defaults to the global names) — approved.
3. **"Sync Now" syncs the selected project only**; syncing all projects is done by cron — approved.
4. **All authenticated users see all tracked projects** — approved.
5. One cron interval for all projects (current 15 min) — recommended default, not contested.

## Known Limitations (documented system limits)
- **Sizing**: the design and testing target is **at most 10 tracked projects with at most ~1,000 issues each**. Beyond that a cron run may exceed shared-hosting time/memory limits or GitHub's hourly GraphQL quota (5,000 points per token). This limit must be stated in `README.md`/`DEPLOYMENT.md` (T055) and the UI/admin guidance (soft warning when more than 10 projects are tracked).
- **No per-project access control**: every logged-in user can open every tracked project.
- Sync Now never syncs "all projects" from a web request (host time limit); use cron.

## Future Improvements (not in Phase 8)
- **Per-project access control** (roles/membership per project; restrict the switcher and API by project) — recorded 2026-10-25 as the first follow-up candidate.
- Per-project sync schedules; webhooks for near-real-time updates; cross-project portfolio dashboards; GitHub App/OAuth instead of PATs (see REQ-ADD-002).

## Implementation Plan
T046 data model → T048 account API/client → T050 project tracking API + admin UI → T051 sync orchestration → T052 project switcher → T053 migration/compat → T054 security review → T055 verification and release.

## Acceptance Criteria
- An admin can discover and track at least two projects from one or two accounts; both sync via cron and appear in the project switcher.
- Selecting a project updates Dashboard, Issues, Members and Sync consistently and is remembered after reload.
- One failing project (bad field mapping, revoked token) does not block the other; its status is visible.
- Untracking keeps data and hides the project (archived); re-tracking restores it.
- Snapshot files of different projects never collide.
- Existing single-project deployments upgrade without data loss.

## Approval Status
- [x] Owner confirms open questions (2026-10-25)
- [x] Approved for implementation

## Related
- Tasks: [T046](../../tasks/task-dev-account-data-model.md), [T050](../../tasks/task-dev-project-tracking.md), [T051](../../tasks/task-dev-sync-orchestration.md), [T052](../../tasks/task-dev-project-switcher.md), [T053](../../tasks/task-dev-env-import-compat.md), [T055](../../tasks/task-dev-phase8-verification.md)
- Related change: [REQ-ADD-002](2026-10-24-REQ-ADD-002-admin-github-account-config.md)
