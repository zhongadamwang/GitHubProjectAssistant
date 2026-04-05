# T021 — Build API Service Layer

**Task ID**: T021  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Create a centralized Axios-based API client used by all Pinia stores and components. The module exposes named async functions that wrap every backend endpoint. A 401 response interceptor auto-triggers logout and redirect to `/login`.

### Acceptance Criteria
- [ ] `frontend/src/services/api.js` creates an Axios instance with `baseURL: '/api'` and `withCredentials: true`
- [ ] Response interceptor: on HTTP 401, calls `authStore.logout()` and navigates to `/login`
- [ ] Named methods exported (all return unwrapped `response.data`):
  - `login(email, password)` → `POST /auth/login`
  - `logout()` → `POST /auth/logout`
  - `getMe()` → `GET /auth/me`
  - `getProjects()` → `GET /projects`
  - `getProject(id)` → `GET /projects/{id}`
  - `getIssues(projectId, params)` → `GET /projects/{id}/issues` (supports filter query params)
  - `updateIssueTime(issueId, data)` → `PUT /issues/{id}/time`
  - `getBurndown(projectId, iteration)` → `GET /projects/{id}/burndown?iteration=...`
  - `getMembers(projectId, iteration)` → `GET /projects/{id}/members?iteration=...`
  - `getSyncHistory()` → `GET /sync/history`
  - `triggerSync()` → `POST /sync/trigger`
  - `getUsers()` → `GET /admin/users`
  - `createUser(data)` → `POST /admin/users`

### Tasks/Subtasks
- [ ] Create `frontend/src/services/api.js` — Axios instance with `baseURL` and `withCredentials`
- [ ] Add response interceptor for 401 → logout + redirect
- [ ] Implement all 13 named methods with correct HTTP verbs and paths
- [ ] Export all methods as named exports (and optionally a default `api` instance)

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Each method maps to the correct endpoint defined in `config/routes.php`
- [ ] 401 response on any call triggers logout and navigates to `/login`
- [ ] No raw `axios.get(...)` calls in stores or components — all go through `api.js`

### Dependencies
- T018 — Vue 3 Vite project scaffold

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — Required by all store-level data fetching (T022–T026)

### Labels/Tags
- Category: development
- Component: frontend, api, service
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Endpoint paths must exactly match `config/routes.php` definitions from T006
- `updateIssueTime` body: `{ "estimated_hours": float, "remaining_hours": float, "actual_hours": float }` — all fields optional (PATCH semantics over PUT route)
- `getBurndown` should omit `?iteration=` param when `iteration` is null/undefined
- Source Requirements: R-012

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
