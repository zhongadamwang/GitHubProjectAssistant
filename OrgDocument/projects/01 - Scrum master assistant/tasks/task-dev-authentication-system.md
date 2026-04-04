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
- [ ] `AuthService` validates login with `password_verify()`, creates PHP session, sets httpOnly + secure cookie flags
- [ ] `AuthController` provides `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- [ ] `AuthMiddleware` returns 401 for unauthenticated requests on protected routes
- [ ] `AdminMiddleware` returns 403 for non-admin users on admin routes
- [ ] Session fixation protection: regenerate session ID on login
- [ ] `UserRepository` provides `findByEmail()`, `findById()`, `create()` with parameterized queries
- [ ] Login returns user info (`id`, `email`, `display_name`, `role`) — never returns `password_hash`
- [ ] Invalid credentials return 401 with generic error message (no user enumeration)

### Tasks/Subtasks
- [ ] Create `src/Models/User.php` — user entity class with typed properties
- [ ] Implement `src/Repositories/UserRepository.php` — `findByEmail()`, `findById()`, `create()` using PDO prepared statements
- [ ] Implement `src/Services/AuthService.php` — `login()`, `logout()`, `getCurrentUser()`, session management
- [ ] Configure PHP session: httpOnly cookies, secure flag, SameSite=Lax, custom session name
- [ ] Add session fixation protection: `session_regenerate_id(true)` on successful login
- [ ] Implement `src/Controllers/AuthController.php` — handle login/logout/me HTTP requests
- [ ] Implement `src/Middleware/AuthMiddleware.php` — check session, return 401 if missing
- [ ] Implement `src/Middleware/AdminMiddleware.php` — check role, return 403 if not admin
- [ ] Validate login input: require email and password, sanitize input
- [ ] Test: successful login returns user data (no password_hash)
- [ ] Test: invalid credentials return 401 with generic message
- [ ] Test: protected routes return 401 without session
- [ ] Test: admin routes return 403 for member role

### Definition of Done
- [ ] All acceptance criteria met
- [ ] No password hashes exposed in any API response
- [ ] Session fixation protection verified
- [ ] All parameterized queries (no SQL injection)
- [ ] Generic error messages (no user enumeration)

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
*No updates yet*

---
**Status**: Not Started  
**Last Updated**: 2026-04-02
