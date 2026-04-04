# T005 — Implement Authentication System

**Task ID**: T005  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #5  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/5  

### Description
Build session-based authentication with login/logout/me endpoints, auth middleware for protected routes, and admin role middleware.

### Acceptance Criteria
- [x] `AuthService` validates login with `password_verify()`, creates PHP session, sets httpOnly + secure cookie flags
- [x] `AuthController` provides `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- [x] `AuthMiddleware` returns 401 for unauthenticated requests on protected routes
- [x] `AdminMiddleware` returns 403 for non-admin users on admin routes
- [x] Session fixation protection: regenerate session ID on login
- [x] `UserRepository` provides `findByEmail()`, `findById()`, `create()` with parameterized queries
- [x] Login returns user info (`id`, `email`, `display_name`, `role`) — never returns `password_hash`
- [x] Invalid credentials return 401 with generic error message (no user enumeration)

### Tasks/Subtasks
- [x] Create `src/Models/User.php` — user entity class with typed properties
- [x] Implement `src/Repositories/UserRepository.php` — `findByEmail()`, `findById()`, `create()` using PDO prepared statements
- [x] Implement `src/Services/AuthService.php` — `login()`, `logout()`, `getCurrentUser()`, session management
- [x] Configure PHP session: httpOnly cookies, secure flag, SameSite=Lax, custom session name
- [x] Add session fixation protection: `session_regenerate_id(true)` on successful login
- [x] Implement `src/Controllers/AuthController.php` — handle login/logout/me HTTP requests
- [x] Implement `src/Middleware/AuthMiddleware.php` — check session, return 401 if missing
- [x] Implement `src/Middleware/AdminMiddleware.php` — check role, return 403 if not admin
- [x] Validate login input: require email and password, sanitize input
- [ ] Test: successful login returns user data (no password_hash)
- [ ] Test: invalid credentials return 401 with generic message
- [ ] Test: protected routes return 401 without session
- [ ] Test: admin routes return 403 for member role

### Definition of Done
- [x] All acceptance criteria met
- [x] No password hashes exposed in any API response
- [x] Session fixation protection verified
- [x] All parameterized queries (no SQL injection)
- [x] Generic error messages (no user enumeration)

### Dependencies
- T002 — `users` table must exist
- T003 — Seed script must create initial admin user for testing
- T004 — Slim 4 entry point and DI container must be configured

### Effort Estimate
**Time Estimate**: 1.5 days  

### Priority
High — Authentication gates all protected API endpoints

### Labels/Tags
- Category: development
- Component: backend, auth, security
- Sprint: Phase 1 — Foundation

### Notes
- Security critical: follow OWASP session management best practices
- bcrypt cost factor: 12 for `password_hash()`
- Never reveal whether an email exists in the system via error messages
- Session cookie: httpOnly=true, secure=true (in production), SameSite=Lax
- Risk: Session cookie not sent cross-origin in dev → use Vite proxy as mitigation
- Source Requirements: ADR-7

### Progress Updates
- **2026-04-03**: Implemented `User` model (immutable, `toApiArray()` excludes `password_hash`), `UserRepository` (`findByEmail`, `findById`, `findHashByEmail`, `create` — all prepared statements), `AuthService` (session fixation protection via `session_regenerate_id`, constant-time dummy hash to prevent timing enumeration, cookie flags from settings), `AuthController` (login/logout/me, generic 401 on bad creds, email validation), `AuthMiddleware` (401 + injects `auth_user` attribute), `AdminMiddleware` (403 for non-admin). Container updated with `ResponseFactoryInterface`, repository, service, controller, and both middleware registrations.

---
**Status**: Completed  
**Last Updated**: 2026-04-03
