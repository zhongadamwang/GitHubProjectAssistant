# T036 — Code Documentation & Comments

**Task ID**: T036  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-06  
**Assignee**: TBD  
**Sprint**: Phase 6 — Polish & Validation  

### Description
Add PHPDoc blocks to all public PHP classes and methods, and JSDoc comments to all Vue components and service functions. Focus on non-obvious logic: sync pipeline, burndown algorithm, efficiency ratio calculation, auth flow, and cron entry point. Internal helper methods and trivial getters/setters do not require full documentation.

### Acceptance Criteria
- [ ] Every public PHP class in `src/` has a class-level PHPDoc with a one-sentence description
- [ ] Every public method in `src/Services/`, `src/Controllers/`, `src/Repositories/` has a `@param` and `@return` PHPDoc; `@throws` listed for methods that can throw checked exceptions
- [ ] `SyncService::run()` has an inline comment block explaining the 7-step sync cycle
- [ ] `BurndownService::getBurndown()` has an inline comment explaining the ideal-line calculation formula
- [ ] `EfficiencyService::getMemberEfficiency()` has an inline comment explaining the ratio formula and null-safe guard
- [ ] `cron/sync.php` has a file-level comment block explaining the lock-file mechanism and exit codes (0 = success, 1 = error, 2 = already-running)
- [ ] Each Vue component (`*.vue`) in `frontend/src/` has a `<script>` top-level JSDoc comment with `@component`, `@prop` (if applicable), and `@emits` (if applicable) descriptors
- [ ] `frontend/src/services/api.js` has a JSDoc comment on each exported function describing the endpoint, parameters, and return type
- [ ] `frontend/src/stores/*.js` Pinia stores have JSDoc on each action method
- [ ] No dead code, commented-out blocks, or `TODO` / `FIXME` left in production paths (resolve or remove before marking done)

### Tasks/Subtasks
- [ ] **Backend — Services**: add PHPDoc to `GitHubGraphQLService`, `SyncService`, `BurndownService`, `EfficiencyService`, `TimeTrackingService`, `AuthService`; add inline comments for complex algorithms (burndown ideal line, efficiency ratio, sync diff logic)
- [ ] **Backend — Repositories**: add PHPDoc to all public methods in `IssueRepository`, `ProjectRepository`, `BurndownRepository`, `SyncHistoryRepository`, `UserRepository`, `TimeLogRepository`
- [ ] **Backend — Controllers**: add PHPDoc to all public action methods in `AuthController`, `AdminController`, `ProjectController`, `IssueController`, `BurndownController`, `MemberController`, `SyncController`
- [ ] **Backend — Middleware**: add class-level PHPDoc to `AuthMiddleware`, `AdminMiddleware`, `CorsMiddleware`, `JsonResponseMiddleware`
- [ ] **Backend — Cron**: add file-level comment to `cron/sync.php` documenting lock mechanism and exit codes
- [ ] **Frontend — Components**: add JSDoc header to each `*.vue` file in `frontend/src/views/` and `frontend/src/components/`
- [ ] **Frontend — Stores**: add JSDoc to each action in `authStore.js`, `dashboardStore.js`, `projectStore.js`
- [ ] **Frontend — Services**: add JSDoc to each exported function in `frontend/src/services/api.js`
- [ ] **Dead code sweep**: `grep -r "TODO\|FIXME\|console\.log\|var_dump\|print_r" src/ frontend/src/` — remove debug statements; resolve or create follow-up tasks for any TODOs
- [ ] Run `composer test` after all PHP changes to confirm nothing broken

### Definition of Done
- [ ] All acceptance criteria met  
- [ ] `composer test` passes  
- [ ] No `console.log`, `var_dump`, or `print_r` in production paths  
- [ ] All `TODO` / `FIXME` comments resolved or converted to tracked follow-up issues  

### Dependencies
- T032 — End-to-End Integration Testing (code must be stable before documenting)  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
Medium — Improves long-term maintainability; non-blocking for deployment  

### Labels/Tags
- Category: documentation  
- Component: backend, frontend  
- Sprint: Phase 6 — Polish & Validation  

### Notes
- PHPDoc generation with `phpDocumentor` is optional; the goal is in-source documentation, not a generated HTML site  
- Focus effort on complex logic that requires explanation; skip trivial setters/getters and self-evident one-liners  
- Source Requirements: R-010 (maintainability), R-012 (clean architecture)  

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-06
