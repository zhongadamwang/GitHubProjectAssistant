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
- [x] All PDO queries use prepared statements with bound parameters — no dynamic SQL string concatenation (OWASP A03: Injection)
- [x] `password_hash()` uses `PASSWORD_BCRYPT` with cost ≥ 12; no MD5/SHA1 password storage (OWASP A02: Cryptographic Failures)
- [x] Session is regenerated after successful login (`session_regenerate_id(true)`); session cookie has `HttpOnly`, `SameSite=Lax`, and `Secure` flags set in production (OWASP A07: Identification & Authentication Failures)
- [x] GitHub PAT stored only in `.env` (never committed); `.gitignore` confirms `.env` is excluded (OWASP A02)
- [x] All user-supplied input to API endpoints is validated before use: email format, password minimum length, numeric type for hours fields (OWASP A03: Injection / A04: Insecure Design)
- [x] CORS: `CorsMiddleware` restricts `Access-Control-Allow-Origin` to configured `APP_URL` in production — not a wildcard `*` (OWASP A05: Security Misconfiguration)
- [x] No sensitive data (PAT, database password, session secret) is logged to application logs or error output (OWASP A09: Security Logging & Monitoring Failures)
- [x] Error responses in production (`APP_ENV=production`) return generic messages — no PHP stack traces or SQL errors exposed to clients (OWASP A05)
- [x] `public/` directory contains only `index.php`, `.htaccess`, and `dist/`; PHP source files and `.env` are outside document root (OWASP A05)
- [x] Rate limiting consideration documented: `sync/trigger` endpoint notes that it is admin-only (403 for non-admins); no unauthenticated sync trigger path exists (OWASP A04)
- [x] `security-checklist.md` created at `OrgDocument/Solutions/ScrumMasterTool/security-checklist.md` with pass/fail/N-A per item

### Tasks/Subtasks
- [x] **SQL Injection audit**: `grep -r "query\|exec\|prepare" src/` — review every database call; confirm all use `$pdo->prepare()` + `->execute([$params])`; fix any found raw concatenation
- [x] **Session security**: check `AuthService::login()` for `session_regenerate_id(true)`; check `config/settings.php` or `public/index.php` for `session_set_cookie_params()` with `httponly=true`, `samesite=Lax`, `secure` (true in production)
- [x] **Password hashing**: confirm `AuthService` uses `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` and `password_verify()`; confirm seed script uses same
- [x] **CORS review**: open `src/Middleware/CorsMiddleware.php`; ensure `Access-Control-Allow-Origin` reads from `$_ENV['APP_URL']` rather than `*`; ensure pre-flight (`OPTIONS`) handling does not skip auth middleware
- [x] **Error handling in production**: check Slim 4 error handler in `bootstrap/app.php` or `public/index.php`; ensure `displayErrorDetails` is `false` when `APP_ENV !== 'development'`; verify JSON error responses expose only `error` message string
- [x] **Input validation**: review `AuthController`, `IssueController`, `AdminController` for validation of all incoming fields; add `filter_var()` / type-cast guards where missing
- [x] **Secrets in logs**: `grep -r "PAT\|password\|secret\|token" src/ cron/` — verify no logging of sensitive values; redact any found
- [x] **File path security**: confirm `data/snapshots/` directory is outside `public/`; confirm `.htaccess` blocks direct access to `src/`, `config/`, `database/`
- [x] **Dependency audit**: run `composer audit` to check for known CVEs in installed packages; update any flagged minor/patch versions
- [x] Create `OrgDocument/Solutions/ScrumMasterTool/security-checklist.md` with results (pass/fail/N-A + remediation note per item)

### Definition of Done
- [x] All acceptance criteria met  
- [x] `security-checklist.md` created with no open "fail" items  
- [x] `composer test` still passes after any backend changes  
- [x] No raw SQL concatenation found in `src/`  

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

**2026-04-06** — T034 implemented. Full OWASP Top 10 audit completed across all source files. 17 security items reviewed — all PASS, 0 FAIL. Key findings:
- All PDO queries use prepared statements; static `->query()` calls confirmed safe (no user input).
- `password_hash(PASSWORD_BCRYPT, cost=12)` + `password_verify()` confirmed in `UserRepository`.
- `session_regenerate_id(true)` confirmed in `AuthService::login()`; HttpOnly + SameSite=Strict cookie flags configured.
- `CorsMiddleware` uses `APP_CORS_ORIGIN` in production, never wildcard `*`.
- `APP_DEBUG=false` default ensures no stack traces in production error responses.
- `public/` contains only `index.php`, `.htaccess`; all source outside document root confirmed.
- `composer audit` returned no CVEs.
- `security-checklist.md` created at `OrgDocument/Solutions/ScrumMasterTool/security-checklist.md`.
- No code changes required — existing implementation already met all security requirements.

---
**Status**: Completed  
**Last Updated**: 2026-04-06
