# T017 — Implement Admin User Management Endpoints

**Task ID**: T017  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-04  
**Assignee**: TBD  
**Sprint**: Phase 3 — Analytics Engine  

### Description
Build admin-only endpoints for listing and creating users. Completes the `AdminController` (scaffolded in T006 with 501 placeholders) with real implementations backed by `UserRepository`.

### Acceptance Criteria
- [ ] `GET /api/admin/users` returns a JSON array of user objects; each object contains `id`, `email`, `display_name`, `role`, `github_username`, `last_login_at` — `password_hash` is **never** included in any response
- [ ] `POST /api/admin/users` accepts `{email, display_name, password, role, github_username?}` (JSON body); creates the user; returns the new user object (without `password_hash`) with HTTP 201
- [ ] Password hashed with `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` before storage
- [ ] Input validation for `POST`: `email` must be a valid email format; `password` minimum 8 characters; `role` must be one of `admin` or `member`; `display_name` non-empty string; returns 422 with `{errors: {field: message}}` on violation
- [ ] `email` uniqueness enforced at DB level (UNIQUE KEY already on `users.email`); controller catches PDO duplicate-entry exception and returns HTTP 409 `{error: "Email already in use"}`
- [ ] Both endpoints protected by `AdminMiddleware` — non-admin authenticated users receive 403; unauthenticated requests receive 401
- [ ] `AdminController` wired into `config/container.php`; 501 placeholder routes in `config/routes.php` replaced with real calls

### Tasks/Subtasks
- [ ] Add `UserRepository::findAll(): array` — `SELECT id, email, display_name, role, github_username, last_login_at FROM users ORDER BY id ASC`; never selects `password_hash`
- [ ] Add `UserRepository::create(string $email, string $display_name, string $passwordHash, string $role, ?string $githubUsername): int` — inserts row and returns new `id`; throws `\PDOException` on duplicate email (let controller handle)
- [ ] Implement `AdminController::listUsers(Request, Response, array): Response` calling `UserRepository::findAll()`; returns 200 JSON array
- [ ] Implement `AdminController::createUser(Request, Response, array): Response` — parse body, validate fields, hash password, call `UserRepository::create()`; return 201 on success, 409 on duplicate, 422 on validation failure
- [ ] Replace 501 stubs in `config/routes.php` for `GET /api/admin/users` and `POST /api/admin/users` with real `AdminController` method references
- [ ] Wire updated `AdminController` in `config/container.php` (should already exist from T006; add `UserRepository` dependency injection)
- [ ] Write unit test: `AdminControllerTest` covering (a) list returns users without password_hash, (b) create with valid data returns 201, (c) create with duplicate email returns 409, (d) create with invalid email format returns 422, (e) create with password < 8 chars returns 422

### Definition of Done
- [ ] All acceptance criteria met
- [ ] `password_hash` column never appears in any API response
- [ ] 409 returned cleanly on duplicate email (no unhandled exception)
- [ ] All inputs validated before hitting the database
- [ ] Both routes blocked for non-admin users (403) and unauthenticated users (401)

### Dependencies
- T005 — `AuthService`, `UserRepository` (partial), `AuthMiddleware`, `AdminMiddleware` must exist
- T006 — `config/routes.php` and `AdminController` stub; `config/container.php` established

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
Medium — Required by Phase 4 Admin View (T026); not on critical path for burndown/efficiency features

### Labels/Tags
- Category: development
- Component: backend, admin, user-management
- Sprint: Phase 3 — Analytics Engine

### Notes
- `UserRepository` already exists from T005 with `findByEmail()` and `updateLastLogin()` — extend it rather than creating a new class
- The `github_username` field is optional on creation; can be `null`
- No user delete or update endpoint is in scope for Phase 3; that would be a Phase 6 polish item
- Source Requirements: ADR-7

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-04
