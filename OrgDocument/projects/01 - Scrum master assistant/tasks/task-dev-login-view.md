# T019 — Build Login View

**Task ID**: T019  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Create the login page presented to unauthenticated users. The form POSTs credentials to `POST /api/auth/login` using Axios with `withCredentials: true` so the PHP session cookie is stored. On success the user is redirected to the Dashboard; on failure an inline error message is shown.

### Acceptance Criteria
- [ ] `frontend/src/views/LoginView.vue` renders email and password input fields plus a Submit button
- [ ] Axios instance used with `withCredentials: true` so PHP session cookies are sent/received
- [ ] Successful login (HTTP 200) — calls `authStore.fetchMe()` then navigates to `/`
- [ ] Failed login (HTTP 401) — displays inline error: "Invalid email or password"
- [ ] Network/server error — displays inline fallback: "Login failed — please try again"
- [ ] Submit button disabled and shows loading state while request is in-flight
- [ ] Enter key on password field submits the form
- [ ] No credentials stored in Pinia or localStorage — only HTTP session cookie

### Tasks/Subtasks
- [x] Create `frontend/src/views/LoginView.vue` — form with `v-model` bindings, `ref` for loading/error state, `@submit.prevent` handler
- [x] Wire `LoginView` into `frontend/src/router/index.js` at path `/login`
- [x] Import API service (`api.login(email, password)`) — method to be created in T021; stub or inline axios call acceptable here
- [x] Add redirect logic: `router.push('/')` on success, set `errorMessage` ref on failure
- [x] Style: centered card layout, responsive, no external CSS framework required (basic CSS-in-component)

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Form submits to `POST /api/auth/login` with `{"email": "", "password": ""}` JSON body
- [ ] Session cookie visible in browser DevTools after successful login
- [ ] Button disabled state visually apparent during request

### Dependencies
- T018 — Vue 3 + Vite project scaffold must exist

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — Required by T020 (Auth Store) and all authenticated views

### Labels/Tags
- Category: development
- Component: frontend, auth, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Do NOT store raw password anywhere in component state beyond the `v-model` binding needed for submission
- Redirect destination should respect a `redirect` query param if present (e.g., `/login?redirect=/admin`)
- Source Requirements: ADR-7

### Progress Updates
- **2026-04-05**: Created `frontend/src/views/LoginView.vue` — `<script setup>` with `v-model` email/password refs, `loading`/`errorMessage` refs, `@submit.prevent` handler calling `authStore.login()`; 401 → "Invalid email or password", any other error → "Login failed"; button disabled during request; Enter key submits; scoped CSS card layout. Route registered at `/login` in router.

---
**Status**: Completed  
**Last Updated**: 2026-04-05
