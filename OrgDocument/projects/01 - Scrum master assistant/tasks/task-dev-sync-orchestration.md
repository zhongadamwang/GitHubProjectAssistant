# T051 — Sync Orchestration: Per-Project Sync, Cron Loop, Isolation, Rate Limits

**Task ID**: T051  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
Refactor the single-project `SyncService` wiring into an orchestrator that syncs every tracked project with the right account token and field mapping, safely on shared hosting.

### Acceptance Criteria
- [x] `SyncServiceFactory::forProject(project)` builds a `SyncService` from the project's account (via `GitHubClientFactory`), owner/number and field mapping (project override → global defaults)
- [x] `SyncOrchestrator::runAll()` syncs tracked, non-archived projects sequentially; one project's `GitHubApiException`/auth/mapping failure is recorded and does not stop the rest; returns a per-project result list
- [x] Rate-limit guard: after each project the remaining GitHub quota is checked; below a threshold the run stops cleanly and records `rate_limited` for the remaining projects
- [x] `cron/sync.php` uses `runAll()` (keeps the lock file; exit codes: 0 all ok, 1 any API failure, 2 unexpected); per-project log line
- [x] `POST /api/projects/{id}/sync` (admin) syncs one project and returns its result/status; the old `POST /api/sync/trigger` is kept as an alias for the first tracked project or removed (decide and document)
- [x] `sync_history` rows always carry the `project_id` (failures too, once the project is known); `projects.last_sync_status/last_sync_at` updated
- [x] Snapshots written to `data/snapshots/{project_id}/` (no cross-project collisions); retention applied per project; burndown capture per project already keyed by `project_id`
- [x] Failed authentication marks the account status (`invalid/expired`) so the admin UI shows it
- [x] PHPUnit: two projects, one failing; rate-limit stop; snapshot isolation; per-project mapping; cron script smoke

### Tasks/Subtasks
- [x] Factory, orchestrator, container wiring; remove single-project assumptions from `config/settings.php` usage
- [x] Cron + controller endpoint; snapshot path/retention changes
- [x] Tests; update `DEPLOYMENT.md` cron instructions

### Definition of Done
- [x] All acceptance criteria met; single-project deployments behave exactly as before  

### Dependencies
- T046, T048, T050  

### Effort Estimate
**Time Estimate**: 2 days  

### Priority
High — core of multi-project support  

### Progress Updates
- **2026-10-25**: Orchestrator, per-project Sync Now, cron loop, isolation, rate-limit floor, snapshot isolation/retention and legacy fallback complete (28 tests).

---
**Status**: Completed  
**Last Updated**: 2026-10-25
