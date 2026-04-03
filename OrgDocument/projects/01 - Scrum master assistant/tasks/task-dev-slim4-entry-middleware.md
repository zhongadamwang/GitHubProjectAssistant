# T004 — Set Up Slim 4 Entry Point & Core Middleware

**Task ID**: T004  
**Project**: PRJ-01 — Scrum Master Assistant  
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #4  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/4  

### Description
Create the Slim 4 application entry point (`index.php`), configure DI container with MySQL PDO connection, register CORS and JSON response middleware, set up Apache `.htaccess` rewriting.

### Acceptance Criteria
- [ ] `public/index.php` bootstraps Slim 4 app with DI container
- [ ] `config/container.php` registers PDO (MySQL) connection from `.env` variables
- [ ] `config/settings.php` reads all `.env` config values
- [ ] `CorsMiddleware` handles preflight OPTIONS and sets proper headers
- [ ] `JsonResponseMiddleware` ensures all API responses are JSON with correct `Content-Type`
- [ ] `.htaccess` rewrites all non-file requests to `index.php`
- [ ] `GET /api/health` returns `{"status":"ok"}` (smoke test)

### Tasks/Subtasks
- [ ] Create `public/index.php` — require autoloader, load `.env`, create Slim app
- [ ] Create `config/settings.php` — read `.env` into structured settings array
- [ ] Create `config/container.php` — register PDO factory with MySQL DSN, error mode exception
- [ ] Implement `src/Middleware/CorsMiddleware.php` — handle OPTIONS preflight, set `Access-Control-*` headers
- [ ] Implement `src/Middleware/JsonResponseMiddleware.php` — ensure JSON `Content-Type` on all responses
- [ ] Create `public/.htaccess` — Apache rewrite rules to front controller
- [ ] Register middleware in application bootstrap (CORS → JSON → routing)
- [ ] Add health check route: `GET /api/health` → `{"status":"ok"}`
- [ ] Verify with curl/browser: health endpoint responds correctly

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Health endpoint returns `{"status":"ok"}` with correct headers
- [ ] CORS headers present on preflight and normal responses
- [ ] PDO connection established successfully from `.env` config

### Dependencies
- T001 — Composer project must be initialized with Slim 4 dependencies

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High — Core framework needed by all API endpoints

### Labels/Tags
- Category: development
- Component: backend, middleware
- Sprint: Phase 1 — Foundation

### Notes
- DI container should use PHP-DI or Slim's built-in container
- PDO should use `ERRMODE_EXCEPTION` and `FETCH_ASSOC` defaults
- CORS must allow credentials for session cookie auth
- Can be started in parallel with T001 completion
- Source Requirements: R-009, R-012

### Progress Updates
*No updates yet*

---
**Status**: Not Started  
**Last Updated**: 2026-04-02
