# T026 — Build Admin View (User Management)

**Task ID**: T026  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 4 — Frontend Dashboard  

### Description
Build the admin-only user management page. Admins can view existing users and create new ones. The route guard (T020) prevents non-admin access entirely.

### Acceptance Criteria
- [ ] `AdminView.vue` accessible only to users where `authStore.isAdmin === true` (enforced by route guard AND `v-if` hide as defence-in-depth)
- [ ] Users table columns: Email, Display Name, Role, GitHub Username, Last Login
- [ ] "Add User" form section below the table with fields:
  - Email (required, valid email format)
  - Display Name (required)
  - Password (required, minimum 8 characters)
  - Role (select: `member` / `admin`, default `member`)
  - GitHub Username (optional)
- [ ] Form validation is client-side before submission; invalid fields show inline error text
- [ ] Submit calls `api.createUser(data)`; on success (HTTP 201) — new user appended to table, form resets; on error (HTTP 409 duplicate email) — show "Email already exists"; on HTTP 422 — show field-specific errors
- [ ] Loading state on submit button

### Tasks/Subtasks
- [ ] Create `frontend/src/views/AdminView.vue` — `v-if="authStore.isAdmin"` guard; users list from `api.getUsers()` on mount; inline "Add User" form with `ref` state for all fields; submit handler
- [ ] Register `/admin` route in `frontend/src/router/index.js`; mark `requiresAuth: true, requiresAdmin: true`
- [ ] Add password strength indicator (optional but recommended for admin UX)

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Non-admin users are redirected away by the route guard
- [ ] Adding a user with a duplicate email shows the 409 error message
- [ ] New user appears in the table immediately after successful creation without full page reload

### Dependencies
- T020 — Auth store (`isAdmin` getter, route guard meta)
- T021 — API service layer (`getUsers`, `createUser` required)

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
Medium — Required for initial system setup (admin creates member accounts)

### Labels/Tags
- Category: development
- Component: frontend, admin, user-management, view
- Sprint: Phase 4 — Frontend Dashboard

### Notes
- Password is sent to the backend in plain text over HTTPS — do NOT hash on the frontend
- Role selector should default to `member` to prevent accidental admin creation
- Source Requirements: ADR-7

### Progress Updates
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
