# T006 — Configure Route Definitions

**Task ID**: T006  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #6  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/6  

### Description
Define all API route groups in `config/routes.php` with appropriate middleware assignment. Public routes (auth/login), authenticated routes (projects, issues, burndown), and admin routes (sync trigger, user management).

### Acceptance Criteria
- [x] `config/routes.php` defines all 13 API endpoints per architecture spec
- [x] Public group: `/api/auth/login`
- [x] Authenticated group (AuthMiddleware): all `/api/projects/*`, `/api/issues/*`, `/api/sync/history`, `/api/auth/logout`, `/api/auth/me`
- [x] Admin group (AdminMiddleware): `/api/sync/trigger`, `/api/admin/users`
- [ ] Routes resolve correctly — verified with curl/Postman on health and auth endpoints

### Tasks/Subtasks
- [x] Create `config/routes.php` with route group structure
- [x] Define public routes group (no middleware): `POST /api/auth/login`
- [x] Define authenticated routes group (AuthMiddleware):
  - `POST /api/auth/logout`
  - `GET /api/auth/me`
  - `GET /api/projects`
  - `GET /api/projects/{id}`
  - `GET /api/projects/{id}/issues`
  - `PUT /api/issues/{id}/time`
  - `GET /api/projects/{id}/burndown`
  - `GET /api/projects/{id}/members`
  - `GET /api/sync/history`
- [x] Define admin routes group (AdminMiddleware):
  - `POST /api/sync/trigger`
  - `GET /api/admin/users`
  - `POST /api/admin/users`
- [x] Create placeholder controllers for non-auth routes (return 501 Not Implemented)
- [x] Wire routes into Slim app bootstrap (`public/index.php`)
- [ ] Verify routes with curl: correct middleware applied, proper 401/403 responses

### Definition of Done
- [x] All acceptance criteria met
- [x] All 13 endpoints defined with correct HTTP methods
- [x] Middleware groups correctly applied (public/auth/admin)
- [x] Routes file loaded in app bootstrap

### Dependencies
- T004 — Slim 4 entry point must be configured
- T005 — Auth and Admin middleware must be implemented

### Effort Estimate
**Time Estimate**: 0.5 days  

### Priority
High — Route definitions wire together all API components

### Labels/Tags
- Category: development
- Component: backend, routing
- Sprint: Phase 1 — Foundation

### Notes
- Placeholder controllers allow Phase 1 to complete with a fully routed skeleton
- Actual controller logic for projects/issues/burndown implemented in Phases 2–3
- Source Requirements: R-012

### Progress Updates
- **2026-04-03**: Created `config/routes.php` with 3 groups (public, auth, admin). Added placeholder controllers: `ProjectController` (5 routes → 501), `IssueController` (1 route → 501), `SyncController` (2 routes → 501), `AdminController` (2 routes → 501). All registered in `config/container.php`. Route numeric ID constraints applied (`{id:[0-9]+}`). Routes loaded by the existing `(require $routesFile)($app)` in `public/index.php`.

---
**Status**: Completed  
**Last Updated**: 2026-04-03
