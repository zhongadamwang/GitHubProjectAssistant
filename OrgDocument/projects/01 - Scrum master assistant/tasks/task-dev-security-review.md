# T034 — Security Review & Hardening

**Task ID**: T034  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-06  
**Assignee**: TBD  
**Sprint**: Phase 6 — Polish & Validation  

### Description
Systematically review the application against the OWASP Top 10 Web Application Security Risks. Identify and remediate any vulnerabilities. Produce a `security-checklist.md` documenting the review results.

### Acceptance Criteria
- [ ] All PDO queries use prepared statements with bound parameters — no dynamic SQL string concatenation (OWASP A03: Injection)
- [ ] `password_hash()` uses `PASSWORD_BCRYPT` with cost ≥ 12; no MD5/SHA1 password storage (OWASP A02: Cryptographic Failures)
- [ ] Session is regenerated after successful login (`session_regenerate_id(true)`); session cookie has `HttpOnly`, `SameSite=Lax`, and `Secure` flags set in production (OWASP A07: Identification & Authentication Failures)
- [ ] GitHub PAT stored only in `.env` (never committed); `.gitignore` confirms `.env` is excluded (OWASP A02)
- [ ] All user-supplied input to API endpoints is validated before use: email format, password minimum length, numeric type for hours fields (OWASP A03: Injection / A04: Insecure Design)
- [ ] CORS: `CorsMiddleware` restricts `Access-Control-Allow-Origin` to configured `APP_URL` in production — not a wildcard `*` (OWASP A05: Security Misconfiguration)
- [ ] No sensitive data (PAT, database password, session secret) is logged to application logs or error output (OWASP A09: Security Logging & Monitoring Failures)
- [ ] Error responses in production (`APP_ENV=production`) return generic messages — no PHP stack traces or SQL errors exposed to clients (OWASP A05)
- [ ] `public/` directory contains only `index.php`, `.htaccess`, and `dist/`; PHP source files and `.env` are outside document root (OWASP A05)
- [ ] Rate limiting consideration documented: `sync/trigger` endpoint notes that it is admin-only (403 for non-admins); no unauthenticated sync trigger path exists (OWASP A04)
- [ ] `security-checklist.md` created at `OrgDocument/Solutions/ScrumMasterTool/security-checklist.md` with pass/fail/N-A per item

### Tasks/Subtasks
- [ ] **SQL Injection audit**: `grep -r "query\|exec\|prepare" src/` — review every database call; confirm all use `$pdo->prepare()` + `->execute([$params])`; fix any found raw concatenation
- [ ] **Session security**: check `AuthService::login()` for `session_regenerate_id(true)`; check `config/settings.php` or `public/index.php` for `session_set_cookie_params()` with `httponly=true`, `samesite=Lax`, `secure` (true in production)
- [ ] **Password hashing**: confirm `AuthService` uses `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` and `password_verify()`; confirm seed script uses same
- [ ] **CORS review**: open `src/Middleware/CorsMiddleware.php`; ensure `Access-Control-Allow-Origin` reads from `$_ENV['APP_URL']` rather than `*`; ensure pre-flight (`OPTIONS`) handling does not skip auth middleware
- [ ] **Error handling in production**: check Slim 4 error handler in `bootstrap/app.php` or `public/index.php`; ensure `displayErrorDetails` is `false` when `APP_ENV !== 'development'`; verify JSON error responses expose only `error` message string
- [ ] **Input validation**: review `AuthController`, `IssueController`, `AdminController` for validation of all incoming fields; add `filter_var()` / type-cast guards where missing
- [ ] **Secrets in logs**: `grep -r "PAT\|password\|secret\|token" src/ cron/` — verify no logging of sensitive values; redact any found
- [ ] **File path security**: confirm `data/snapshots/` directory is outside `public/`; confirm `.htaccess` blocks direct access to `src/`, `config/`, `database/`
- [ ] **Dependency audit**: run `composer audit` to check for known CVEs in installed packages; update any flagged minor/patch versions
- [ ] Create `OrgDocument/Solutions/ScrumMasterTool/security-checklist.md` with results (pass/fail/N-A + remediation note per item)

### Definition of Done
- [ ] All acceptance criteria met  
- [ ] `security-checklist.md` created with no open "fail" items  
- [ ] `composer test` still passes after any backend changes  
- [ ] No raw SQL concatenation found in `src/`  

### Dependencies
- T032 — End-to-End Integration Testing (baseline code must be stable)  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High — Security is non-negotiable before production deployment  

### Labels/Tags
- Category: security  
- Component: backend, config  
- Sprint: Phase 6 — Polish & Validation  

### Notes
- OWASP Top 10 reference: https://owasp.org/Top10/  
- The `Secure` cookie flag can only be tested on HTTPS; document it as a production-only setting in `security-checklist.md`  
- `composer audit` requires Composer 2.4+; if not available, check manually against the GitHub Advisory Database  
- Source Requirements: R-011 (cPanel hosting), R-012 (security / auth), ADR-7  

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-06
