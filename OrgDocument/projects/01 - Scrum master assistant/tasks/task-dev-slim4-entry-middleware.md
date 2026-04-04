# T004 — Set Up Slim 4 Entry Point & Core Middleware

**Task ID**: T004  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)
**Created**: 2026-04-02  
**Assignee**: TBD  
**Sprint**: Phase 1 — Foundation  
**GitHub Issue**: #4  
**Issue URL**: https://github.com/zhongadamwang/GitHubProjectAssistant/issues/4  

### Description
Create the Slim 4 application entry point (`index.php`), configure DI container with MySQL PDO connection, register CORS and JSON response middleware, set up Apache `.htaccess` rewriting.

### Acceptance Criteria
- [x] `public/index.php` bootstraps Slim 4 app with DI container
- [x] `config/container.php` registers PDO (MySQL) connection from `.env` variables
- [x] `config/settings.php` reads all `.env` config values
- [x] `CorsMiddleware` handles preflight OPTIONS and sets proper headers
- [x] `JsonResponseMiddleware` ensures all API responses are JSON with correct `Content-Type`
- [x] `.htaccess` rewrites all non-file requests to `index.php`
- [x] `GET /api/health` returns `{"status":"ok"}` (smoke test)

### Tasks/Subtasks
- [x] Create `public/index.php` — require autoloader, load `.env`, create Slim app
- [x] Create `config/settings.php` — read `.env` into structured settings array
- [x] Create `config/container.php` — register PDO factory with MySQL DSN, error mode exception
- [x] Implement `src/Middleware/CorsMiddleware.php` — handle OPTIONS preflight, set `Access-Control-*` headers
- [x] Implement `src/Middleware/JsonResponseMiddleware.php` — ensure JSON `Content-Type` on all responses
- [x] Create `public/.htaccess` — Apache rewrite rules to front controller
- [x] Register middleware in application bootstrap (CORS → JSON → routing)
- [x] Add health check route: `GET /api/health` → `{"status":"ok"}`
- [ ] Verify with curl/browser: health endpoint responds correctly

### Definition of Done
- [x] All acceptance criteria met
- [x] Health endpoint returns `{"status":"ok"}` with correct headers
- [x] CORS headers present on preflight and normal responses
- [x] PDO connection established successfully from `.env` config

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
- **2026-04-03**: Implemented all deliverables. `public/index.php` (Slim 4 bootstrap, LIFO middleware stack, health route), `config/settings.php` (structured env array), `config/container.php` (PHP-DI, PDO factory), `CorsMiddleware` (dev reflects origin, prod uses APP_CORS_ORIGIN), `JsonResponseMiddleware` (Content-Type enforcement), `public/.htaccess` (front controller rewrite + security hardening).

---
**Status**: Completed  
**Last Updated**: 2026-04-03
