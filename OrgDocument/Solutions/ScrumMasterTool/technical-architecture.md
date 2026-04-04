# Technical Architecture — ScrumMasterTool

**Solution**: ScrumMasterTool (SOL-001)  
**Project Reference**: [PRJ-01 — Scrum Master Assistant](../../projects/01%20-%20Scrum%20master%20assistant/main.md)  
**Version**: 1.0.0  
**Created**: 2026-04-03  
**Status**: Approved  

> Authoritative solution-level architecture for ScrumMasterTool. For organization-wide standards see [orgModel/technical-architecture.md](../../orgModel/technical-architecture.md).

---

## Summary

GitHub-integrated Scrum burndown dashboard using **PHP 8.2 + Slim 4** backend, **MySQL** database, **Vue 3 + Chart.js** frontend, and **PHP session-based authentication**. Deployed to cPanel shared hosting via GitHub Actions SFTP. Clean REST API backend serving a compiled SPA, with cron-based **GitHub GraphQL v4** sync and file-based historical snapshots.

### Technology Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Backend | PHP 8.2 + Slim 4 | Universal cPanel support, clean PSR-7 routing, lightweight |
| Database | MySQL 5.7+/8.0 via PDO | cPanel includes MySQL + phpMyAdmin, robust concurrency for 6–15 users |
| Frontend | Vue 3 + Chart.js 4 + Vite | Reactive SPA, mature charting library, small bundle |
| Auth | PHP sessions + bcrypt | Simple, proven, no external dependencies |
| GitHub API | GraphQL v4 | Single query per sync, cursor pagination, ~10–20 points/cycle |
| Deployment | GitHub Actions → SFTP | Automated CI/CD to cPanel shared hosting |
| Sync | cPanel cron (every 15 min) | Shared hosting compatible, GitHub rate-limit safe |

---

## Architecture Decision Records

### ADR-1: Backend → PHP 8.2+ with Slim 4 micro-framework
**Rationale**: cPanel shared hosting universally supports PHP. Slim 4 provides clean PSR-7/15 routing, DI container, and middleware pipeline with minimal overhead.  
**Traceability**: R-009, R-011, R-012

### ADR-2: Database → MySQL via PDO
**Rationale**: cPanel includes MySQL with phpMyAdmin. Better concurrency than SQLite for 6–15 simultaneous users. Proper transaction support, robust locking, native JSON column type.  
**Traceability**: R-011, R-003

### ADR-3: Frontend → Vue 3 (Composition API) + Chart.js 4 + Vite
**Rationale**: Vue 3 offers reactive UI with small bundle size. Chart.js 4 is the most mature, lightweight charting library. Vite compiles to static assets deployed alongside PHP.  
**Traceability**: R-005, R-006, R-007, R-008, R-012

### ADR-4: Sync Strategy → Cron job every 15 min via GitHub GraphQL v4
**Rationale**: GraphQL v4 fetches project + all issues in a single query (~10–20 points). 15-min sync uses ~40–80 points/hour — well within the 5,000/hour rate limit. cPanel natively supports cron scheduling.  
**Traceability**: R-001, R-002

### ADR-5: Historical Snapshots → JSON files on disk
**Rationale**: Each sync writes a timestamped JSON snapshot to `data/snapshots/YYYY-MM-DD_HH-mm.json`. Cheaper than DB BLOBs, easy to inspect, trivial to back up.  
**Traceability**: R-003

### ADR-6: Deployment → GitHub Actions + SFTP to cPanel
**Rationale**: GitHub Actions builds Vue frontend → uploads compiled assets + PHP backend to cPanel via SFTP. Includes MySQL migration step post-deploy.  
**Traceability**: R-009, R-010

### ADR-7: Authentication → PHP session-based with password hashing
**Rationale**: `password_hash()`/`password_verify()` (bcrypt cost 12). PHP sessions stored server-side. Slim 4 middleware checks session on all `/api/*` routes. Role-based: `admin` and `member`.  
**Traceability**: Security best practice, team access control

---

## Solution Folder Structure

```
OrgDocument/Solutions/ScrumMasterTool/
├── technical-architecture.md    ← This file
├── composer.json                ← PHP dependencies (Slim 4, phpdotenv, php-di)
├── .env.example                 ← Config template (DB, GitHub PAT, session)
├── .gitignore                   ← Excludes vendor/, .env, snapshot JSON files
├── public/                      ← cPanel document root
│   ├── index.php                ← Slim 4 entry point
│   ├── .htaccess                ← Apache URL rewrite
│   └── dist/                    ← Compiled Vue SPA assets
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── ProjectController.php
│   │   ├── IssueController.php
│   │   ├── BurndownController.php
│   │   ├── MemberController.php
│   │   └── SyncController.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── GitHubGraphQLService.php
│   │   ├── BurndownService.php
│   │   ├── EfficiencyService.php
│   │   └── TimeTrackingService.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── ProjectRepository.php
│   │   ├── IssueRepository.php
│   │   ├── SyncHistoryRepository.php
│   │   └── TimeLogRepository.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Issue.php
│   │   └── BurndownPoint.php
│   ├── GraphQL/
│   │   ├── queries.php
│   │   └── ResponseParser.php
│   └── Middleware/
│       ├── AuthMiddleware.php
│       ├── AdminMiddleware.php
│       ├── CorsMiddleware.php
│       └── JsonResponseMiddleware.php
├── config/
│   ├── settings.php
│   ├── routes.php
│   └── container.php
├── database/
│   ├── migrations/
│   ├── seeds/
│   └── migrate.php
├── cron/
│   └── sync.php
├── data/
│   └── snapshots/
└── frontend/         ← Vue 3 SPA (built output goes to public/dist/)
    ├── src/
    │   ├── views/
    │   ├── components/
    │   ├── services/
    │   └── stores/
    ├── package.json
    └── vite.config.js
```

---

## API Endpoints (Slim 4)

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| POST | `/api/auth/login` | Authenticate user, start session | Public |
| POST | `/api/auth/logout` | Destroy session | Any |
| GET | `/api/auth/me` | Current user info + role | Any |
| GET | `/api/projects` | List synced GitHub projects | Any |
| GET | `/api/projects/{id}` | Project detail with sprint info | Any |
| GET | `/api/projects/{id}/issues` | Enhanced issues with time data | Any |
| PUT | `/api/issues/{id}/time` | Update estimated/remaining/actual | Member+ |
| GET | `/api/projects/{id}/burndown` | Burndown data (ideal + actual) | Any |
| GET | `/api/projects/{id}/members` | Member efficiency metrics | Any |
| GET | `/api/sync/history` | Sync audit log | Any |
| POST | `/api/sync/trigger` | Manual sync trigger | Admin |
| GET | `/api/admin/users` | List users | Admin |
| POST | `/api/admin/users` | Create user | Admin |

---

## Database Schema

See [project analysis artifact](../../projects/01%20-%20Scrum%20master%20assistant/artifacts/Analysis/technical-architecture.md#database-schema-mysql-57--80) for the full DDL. Six tables: `users`, `projects`, `issues`, `time_logs`, `sync_history`, `burndown_daily`.

---

## Security Checklist

- [x] Passwords: bcrypt cost 12 via `password_hash()` / `password_verify()`
- [x] PDO prepared statements on all DB queries — no string interpolation
- [x] Session cookies: `httpOnly`, `secure`, `SameSite=Strict`
- [x] GitHub PAT in `.env` only, excluded from git
- [x] `AuthMiddleware` on all `/api/*` routes except login
- [x] `AdminMiddleware` on all `/api/admin/*` and `/api/sync/trigger` routes
