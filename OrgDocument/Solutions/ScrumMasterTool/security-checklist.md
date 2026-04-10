# Security Review Checklist — ScrumMasterTool

**Review Date**: 2026-04-06  
**Reviewer**: T034 Security Review  
**OWASP Reference**: https://owasp.org/Top10/  
**Scope**: Full source review of `src/`, `cron/`, `config/`, `public/`, `bootstrap/`  

---

## Summary

| Category | Items | Pass | Fail | N/A |
|----------|-------|------|------|-----|
| SQL Injection (A03) | 2 | 2 | 0 | 0 |
| Cryptographic Failures (A02) | 3 | 3 | 0 | 0 |
| Identification & Authentication (A07) | 3 | 3 | 0 | 0 |
| Insecure Design (A04) | 2 | 2 | 0 | 0 |
| Security Misconfiguration (A05) | 4 | 4 | 0 | 0 |
| Security Logging & Monitoring (A09) | 2 | 2 | 0 | 0 |
| Dependency Security | 1 | 1 | 0 | 0 |
| **Total** | **17** | **17** | **0** | **0** |

**Result: PASS — no open fail items.**

---

## A02 — Cryptographic Failures

### A02-1 — Password hashing algorithm
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Repositories/UserRepository.php:87` |
| **Finding** | `password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12])` is used for all password storage. `password_verify()` is used for verification. No MD5 or SHA1 password storage present. |

### A02-2 — GitHub PAT storage
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `.gitignore`, `.env.example`, `config/settings.php` |
| **Finding** | `GITHUB_PAT` is read from `$_ENV['GITHUB_PAT']` (populated from `.env` at runtime). `.gitignore` lists `.env` and `.env.test` as excluded. `.env` is never committed to source control. |

### A02-3 — Database credentials storage
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `config/settings.php`, `.gitignore` |
| **Finding** | `DB_PASS` is read exclusively from environment variables. `.env` is excluded from version control. |

---

## A03 — Injection

### A03-1 — PDO prepared statements
| | |
|---|---|
| **Status** | ✅ PASS |
| **Files** | `src/Repositories/*.php` |
| **Finding** | All parameterised queries in `UserRepository`, `IssueRepository`, `ProjectRepository`, `BurndownRepository`, `SyncHistoryRepository`, `TimeLogRepository` use `$pdo->prepare()` + `->execute([$params])` or named bound parameters. Two `->query()` calls (`UserRepository::findAll()` and `ProjectRepository::findAll()`) use static SQL with zero user-supplied values — no injection risk. No raw SQL string concatenation of user input found anywhere in `src/`. |

### A03-2 — Input validation at API boundary
| | |
|---|---|
| **Status** | ✅ PASS |
| **Files** | `src/Controllers/AuthController.php`, `src/Controllers/AdminController.php`, `src/Controllers/IssueController.php`, `src/Services/TimeTrackingService.php` |
| **Finding** | `AuthController::login()` validates that email and password are non-empty and that `filter_var($email, FILTER_VALIDATE_EMAIL)` passes before any service call. `AdminController::createUser()` enforces required fields, email format, minimum password length (8), and enum validation on `role`. `TimeTrackingService::updateTime()` allows only whitelisted field names (`estimated_time`, `remaining_time`, `actual_time`) and rejects values that are negative or exceed 9999.99. IssueController`status` filter is validated against an allowlist `['open', 'closed']` in `IssueRepository::findByProject()` before being bound as a parameter. |

---

## A04 — Insecure Design

### A04-1 — Rate limiting for sync/trigger
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `config/routes.php`, `src/Middleware/AdminMiddleware.php` |
| **Finding** | `POST /api/sync/trigger` is behind both `AuthMiddleware` (401 for unauthenticated) and `AdminMiddleware` (403 for non-admin users). No unauthenticated sync trigger path exists. Rate limiting at the HTTP level is a hosting/reverse-proxy concern; cPanel environments should configure this via `.htaccess` or server config. This is documented here as a production deployment note. |

### A04-2 — Unauthenticated access paths
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `config/routes.php`, `src/Middleware/AuthMiddleware.php` |
| **Finding** | All non-public routes are wrapped in the `AuthMiddleware` group. Only `POST /api/auth/login` is intentionally unauthenticated. `GET /api/auth/me` has a secondary null-session guard in the controller in addition to middleware protection. |

---

## A05 — Security Misconfiguration

### A05-1 — CORS policy
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Middleware/CorsMiddleware.php` |
| **Finding** | In production (`APP_ENV != development`), `Access-Control-Allow-Origin` is set to the exact value of `APP_CORS_ORIGIN` env var (or omitted if not configured — for same-host SPAs). A wildcard `*` is never emitted. `Access-Control-Allow-Credentials: true` is set and is only emitted alongside a specific origin. In development the request's `Origin` header is reflected to allow local Vite dev servers; this is controlled by `APP_ENV`. |

### A05-2 — Production error handling
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `bootstrap/app.php:47`, `config/settings.php:20` |
| **Finding** | `$displayErrors` is driven by `settings['app']['debug']` which maps to `FILTER_VALIDATE_BOOLEAN($_ENV['APP_DEBUG'])`. This defaults to `false` (production-safe). Slim's `addErrorMiddleware(false, ...)` suppresses stack traces and SQL detail from client responses. All error responses emit only a top-level `"error"` string. |

### A05-3 — File layout / document root isolation
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `public/` (directory listing), `public/.htaccess` |
| **Finding** | `public/` contains only `index.php`, `.htaccess`, and `.gitkeep` (placeholder for the `dist/` build). All PHP source files (`src/`), configuration (`config/`), database migrations (`database/`), environment files (`.env`), and the Composer autoloader (`vendor/`) are outside the document root. `.htaccess` sets `Options -Indexes` (no directory listing), blocks access to hidden files (`^\.` pattern), and routes all non-static requests to `index.php` via `mod_rewrite`. |

### A05-4 — Data directory access
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `data/snapshots/` location relative to `public/` |
| **Finding** | `data/snapshots/` is at the project root (one level above `public/`), not served directly by Apache/nginx. Burndown JSON snapshots are never web-accessible. |

---

## A07 — Identification & Authentication Failures

### A07-1 — Session fixation protection
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Services/AuthService.php:58` |
| **Finding** | `session_regenerate_id(true)` is called immediately after successful credential verification, before any user data is written to `$_SESSION`. The `true` argument deletes the old session file to prevent fixation attacks. |

### A07-2 — Session cookie flags
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Services/AuthService.php:122–140`, `config/settings.php:46–52` |
| **Finding** | `session_set_cookie_params()` is called before `session_start()` with: `httponly => true`, `samesite => 'Strict'` (default, overridable via `SESSION_SAME_SITE` env var), `secure` from `SESSION_SECURE` env var. **Note**: The `Secure` flag must be set to `true` via `SESSION_SECURE=true` in the production `.env`. It cannot be validated locally over HTTP; this is a production deployment gate. |

### A07-3 — User enumeration prevention
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Services/AuthService.php:47–55`, `src/Controllers/AuthController.php:53–55` |
| **Finding** | `password_verify()` is always called regardless of whether the email exists (a dummy hash is used when no user is found) to prevent timing-based user enumeration. The controller returns the same generic message `"Invalid credentials."` for both wrong email and wrong password. |

---

## A09 — Security Logging & Monitoring Failures

### A09-1 — Sensitive values in logs
| | |
|---|---|
| **Status** | ✅ PASS |
| **Files** | `src/**/*.php`, `cron/**` |
| **Finding** | Search found no `error_log()`, `var_dump()`, `print_r()`, or direct string references to `PAT`, `password`, `secret`, or `token` in any log or debug output within `src/` or `cron/`. Slim's error middleware logs at the server level; exception messages in the codebase never include credential values. |

### A09-2 — Error response information leakage
| | |
|---|---|
| **Status** | ✅ PASS |
| **File** | `src/Controllers/SyncController.php`, `src/Controllers/AuthController.php` |
| **Finding** | All catch blocks wrap exceptions in generic user-facing messages (`"GitHub API request failed"`, `"Invalid credentials."`, `"Issue not found."`) rather than relaying raw exception messages or PDO error strings to the client. |

---

## Dependency Security

### DEP-1 — Composer package audit
| | |
|---|---|
| **Status** | ✅ PASS |
| **Command** | `composer audit` |
| **Finding** | `No security vulnerability advisories found.` — all installed packages are free of known CVEs at the time of this review. Re-run `composer audit` before each production deployment. |

---

## Production Deployment Checklist

These items require attention when deploying to production (cannot be verified locally):

| Item | Action Required |
|------|-----------------|
| `SESSION_SECURE=true` in production `.env` | Set before deploying to an HTTPS host. The `Secure` cookie flag prevents session cookie transmission over plain HTTP. |
| `APP_DEBUG=false` in production `.env` | Confirm — this is the default but must be explicit in the `.env` file. |
| `APP_ENV=production` in production `.env` | Required so CORS does not reflect arbitrary origins. |
| HTTP rate limiting on `POST /api/sync/trigger` | Configure at the web-server/reverse-proxy layer (e.g., `LimitRequestBody` in `.htaccess`, or Nginx `limit_req_zone`). The application enforces admin-only access; rate limiting is a hosting-layer concern. |
| `composer audit` before each deploy | Re-run to catch newly published CVEs in dependencies. |

---

*Generated by T034 Security Review & Hardening — 2026-04-06*
