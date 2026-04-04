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
- [ ] `config/routes.php` defines all 13 API endpoints per architecture spec
- [ ] Public group: `/api/auth/login`
- [ ] Authenticated group (AuthMiddleware): all `/api/projects/*`, `/api/issues/*`, `/api/sync/history`, `/api/auth/logout`, `/api/auth/me`
- [ ] Admin group (AdminMiddleware): `/api/sync/trigger`, `/api/admin/users`
- [ ] Routes resolve correctly — verified with curl/Postman on health and auth endpoints

### Tasks/Subtasks
- [ ] Create `config/routes.php` with route group structure
- [ ] Define public routes group (no middleware): `POST /api/auth/login`
- [ ] Define authenticated routes group (AuthMiddleware):
  - `POST /api/auth/logout`
  - `GET /api/auth/me`
  - `GET /api/projects`
  - `GET /api/projects/{id}`
  - `GET /api/projects/{id}/issues`
  - `PUT /api/issues/{id}/time`
  - `GET /api/projects/{id}/burndown`
  - `GET /api/projects/{id}/members`
  - `GET /api/sync/history`
- [ ] Define admin routes group (AdminMiddleware):
  - `POST /api/sync/trigger`
  - `GET /api/admin/users`
  - `POST /api/admin/users`
- [ ] Create placeholder controllers for non-auth routes (return 501 Not Implemented)
- [ ] Wire routes into Slim app bootstrap (`public/index.php`)
- [ ] Verify routes with curl: correct middleware applied, proper 401/403 responses

### Definition of Done
- [ ] All acceptance criteria met
- [ ] All 13 endpoints defined with correct HTTP methods
- [ ] Middleware groups correctly applied (public/auth/admin)
- [ ] Routes file loaded in app bootstrap

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
*No updates yet*

---
**Status**: Not Started  
**Last Updated**: 2026-04-02
