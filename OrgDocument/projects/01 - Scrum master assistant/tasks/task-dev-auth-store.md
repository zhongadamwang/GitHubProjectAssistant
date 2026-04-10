# T020 — Build Auth Store & Route Guards

**Task ID**: T020  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Implement the Pinia authentication store and Vue Router navigation guards. On every app boot the store calls `GET /api/auth/me` to restore session state. Route guards redirect unauthenticated users to `/login` and restrict `/admin` to users with the `admin` role.

### Acceptance Criteria
- [x] `frontend/src/stores/authStore.js` defines a Pinia store with state: `user` (object | null), `isAuthenticated` (bool), `isAdmin` (bool)
- [x] `login(email, password)` action — calls API, sets `user`, sets `isAuthenticated`
- [x] `logout()` action — calls `POST /api/auth/logout`, clears `user`, sets `isAuthenticated = false`, navigates to `/login`
- [x] `fetchMe()` action — calls `GET /api/auth/me`; on 200 sets `user`; on 401 sets `user = null`
- [x] `isAdmin` getter returns `true` if `user.role === 'admin'`
- [x] `main.js` calls `await authStore.fetchMe()` before `app.mount('#app')` to restore session on page reload
- [x] Vue Router `beforeEach` guard: if route requires auth and `!isAuthenticated` → redirect to `/login`
- [x] Admin-only routes (e.g. `/admin`) guarded: non-admin authenticated users redirected to `/` with error
- [x] Logout clears store state and confirms redirect to `/login`

### Tasks/Subtasks
- [x] Create `frontend/src/stores/authStore.js` — `defineStore('auth', { state, actions, getters })` with `user`, `isAuthenticated`, `isAdmin`; implement `login()`, `logout()`, `fetchMe()`
- [x] Update `frontend/src/router/index.js` — add `meta: { requiresAuth: true }` and `meta: { requiresAdmin: true }` to protected routes
- [x] Add `router.beforeEach()` guard — checks `authStore.isAuthenticated` and `authStore.isAdmin` against route meta
- [x] Update `frontend/src/main.js` — call `authStore.fetchMe()` before mount

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Navigating to `/` while unauthenticated redirects to `/login`
- [ ] Navigating to `/admin` as a non-admin redirects to `/`
- [ ] Hard refresh on an authenticated page restores session without redirect
- [ ] Logout clears state and lands on `/login`

### Dependencies
- T019 — Login view must exist to redirect to

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — Required by all guarded views (T022–T026)

### Labels/Tags
- Category: development
- Component: frontend, auth, store, routing
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- `fetchMe()` should silently swallow 401 errors — a non-logged-in user on page load is not an error
- Use Pinia `defineStore` with Options API syntax for consistency across stores
- Source Requirements: ADR-7

### Progress Updates
- **2026-04-05**: Created `frontend/src/stores/authStore.js` — Pinia Options API store; `user` state; `isAuthenticated`/`isAdmin` getters; `login()` (calls api.login + fetchMe), `logout()` (api.logout + clearAuth + router.push), `fetchMe()` (silently swallows 401), `clearAuth()`. Router `beforeEach` guard added in `router/index.js` checking `requiresAuth` and `requiresAdmin` meta. `main.js` calls `authStore.fetchMe()` before `app.mount()`.

---
**Status**: Completed  
**Last Updated**: 2026-04-05
