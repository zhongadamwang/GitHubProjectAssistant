# Technical Architecture — Scrum Dashboard

**Project**: PRJ-01 — Scrum Master Assistant  
**Version**: 1.0.0  
**Created**: 2026-04-02  
**Status**: Approved  

## Summary

Build a GitHub-integrated Scrum burndown dashboard using **PHP 8.2 + Slim 4** backend, **MySQL** database, **Vue 3 + Chart.js** frontend, and **PHP session-based authentication**. Deployed to cPanel shared hosting via GitHub Actions SFTP. The architecture is a clean REST API backend serving a compiled SPA frontend, with cron-based **GitHub GraphQL v4** sync and file-based historical snapshots.

### Technology Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Backend | PHP 8.2 + Slim 4 | Universal cPanel support, clean PSR-7 routing, lightweight |
| Database | MySQL 5.7+/8.0 via PDO | cPanel includes MySQL + phpMyAdmin, robust concurrency for 6-15 users |
| Frontend | Vue 3 + Chart.js 4 + Vite | Reactive SPA, mature charting library, small bundle |
| Auth | PHP sessions + bcrypt | Simple, proven, no external dependencies |
| GitHub API | GraphQL v4 | Single query per sync, cursor pagination, ~10-20 points/cycle |
| Deployment | GitHub Actions → SFTP | Automated CI/CD to cPanel shared hosting |
| Sync | cPanel cron (every 15 min) | Shared hosting compatible, GitHub rate-limit safe |

---

## Architecture Decision Records

### ADR-1: Backend → PHP 8.2+ with Slim 4 micro-framework
**Rationale**: cPanel shared hosting universally supports PHP. Slim 4 provides clean PSR-7/15 routing, DI container, and middleware pipeline with minimal overhead. PDO for MySQL access.  
**Alternatives considered**: Raw PHP (too messy for maintainability R-012), Laravel (too heavy for shared hosting + lightweight requirement R-011).  
**Traceability**: R-009, R-011, R-012

### ADR-2: Database → MySQL via PDO
**Rationale**: cPanel includes MySQL with phpMyAdmin out of the box. Better concurrency handling than SQLite for 6-15 simultaneous users. Proper transaction support, robust locking, native JSON column type. cPanel provides one-click database creation and user management.  
**Alternatives considered**: SQLite (simpler but weaker concurrent write handling for medium team), PostgreSQL (not universally available on cPanel shared hosting).  
**Traceability**: R-011, R-003

### ADR-3: Frontend → Vue 3 (Composition API) + Chart.js 4 + Vite
**Rationale**: Vue 3 offers reactive UI with small bundle size. Chart.js 4 is the most mature, lightweight charting library — perfect for burndown and bar charts. Vite compiles to static assets that deploy alongside PHP.  
**Alternatives considered**: Alpine.js + HTMX (faster to start, harder to scale chart complexity), React (heavier, no significant advantage here).  
**Traceability**: R-005, R-006, R-007, R-008, R-012

### ADR-4: Sync Strategy → Cron job (cPanel scheduled task) every 15 minutes via GitHub GraphQL v4
**Rationale**: GraphQL v4 allows fetching projects, issues, labels, assignees, and iterations in a single query — reducing API calls from ~4/cycle (REST) to **1-2/cycle**. 5,000 points/hour rate limit; a typical project+issues query costs ~10-20 points, so 15-min sync uses ~40-80 points/hour — extremely safe. Also enables precise field selection (no over-fetching). cPanel natively supports cron scheduling.  
**Alternatives considered**: REST v3 (simpler but requires multiple paginated calls, over-fetches fields), webhooks (shared hosting can't listen for incoming hooks).  
**Traceability**: R-001, R-002

### ADR-5: Historical Snapshots → JSON files on disk
**Rationale**: Each sync writes a timestamped JSON snapshot to `data/snapshots/YYYY-MM-DD_HH-mm.json`. Cheaper than DB BLOBs, easy to inspect, and trivial to back up. Older snapshots can be auto-compressed or purged.  
**Traceability**: R-003

### ADR-6: Deployment → GitHub Actions + lftp (FTP/SFTP) to cPanel
**Rationale**: GitHub Actions builds Vue frontend → uploads compiled assets + PHP backend to cPanel via SFTP. Single workflow file. Most reliable method for shared hosting. Includes MySQL migration step post-deploy.  
**Traceability**: R-009, R-010

### ADR-7: Authentication → PHP session-based with password hashing
**Rationale**: Simple, proven approach that works on any PHP hosting. Users table in MySQL with `password_hash()`/`password_verify()` (bcrypt). PHP sessions stored server-side. No external auth services needed. Slim 4 middleware checks session on all `/api/*` routes; login/logout endpoints are public. Frontend stores session cookie automatically (httpOnly, secure). Role-based: `admin` (can trigger sync, manage users) and `member` (can update time, view dashboards).  
**Alternatives considered**: JWT tokens (stateless but more complex, token refresh logic needed), OAuth via GitHub (adds GitHub dependency for login, overkill for internal team), HTTP Basic Auth (poor UX, no logout capability).  
**Traceability**: Security best practice, team access control

---

## System Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                     cPanel Shared Hosting                      │
│                                                                │
│  ┌──────────────────────┐    ┌──────────────────────────────┐  │
│  │  Vue 3 SPA (static)  │←──│   PHP 8.2 + Slim 4 API      │  │
│  │  • LoginView.vue     │    │   • /api/auth/login|logout   │  │
│  │  • DashboardView.vue │    │   • /api/projects            │  │
│  │  • IssuesView.vue    │    │   • /api/issues/{id}/time    │  │
│  │  • MembersView.vue   │    │   • /api/projects/{id}/burn  │  │
│  │  • AdminView.vue     │    │   • /api/admin/users         │  │
│  │  • Chart.js 4        │    │   • AuthMiddleware (session)  │  │
│  └──────────────────────┘    └───────────┬──────────────────┘  │
│                                          │                     │
│               ┌──────────────────────────┼───────────┐         │
│               │     Business Logic       │           │         │
│               │  • AuthService           │           │         │
│               │  • GitHubGraphQLService  │           │         │
│               │  • BurndownService       │           │         │
│               │  • EfficiencyService     │           │         │
│               │  • TimeTrackingService   │           │         │
│               └──────────────────────────┼───────────┘         │
│                                          │                     │
│    ┌────────────────────┐    ┌───────────┴───────────┐         │
│    │  data/snapshots/   │    │     MySQL (PDO)       │         │
│    │  (JSON audit trail)│    │  6 tables: users,     │         │
│    └────────────────────┘    │  projects, issues,    │         │
│                              │  time_logs, sync_     │         │
│                              │  history, burndown    │         │
│    ┌────────────────────┐    └───────────────────────┘         │
│    │  Cron: sync.php    │                                      │
│    │  (every 15 min)    │───────→ GitHub GraphQL v4 API        │
│    └────────────────────┘         (read-only, PAT Bearer)      │
└────────────────────────────────────────────────────────────────┘
         ▲
         │ SFTP deploy
┌────────┴────────┐
│  GitHub Actions  │
│  Build Vue → FTP │
└─────────────────┘
```

### Layer 1 — Presentation (Vue 3 SPA)
- **Login View**: Email/password form, session-based cookie auth, auto-redirect to dashboard
- **Dashboard View**: Sprint selector, burndown chart (Chart.js line chart with ideal vs actual curves), sprint health indicator
- **Issues View**: Table of enhanced issues with inline time editing (estimated, remaining, actual), filters by assignee/label/status
- **Members View**: Efficiency analysis bar charts (estimated vs actual time per member), accuracy trend lines
- **Sync Status View**: Last sync timestamp, sync history log, manual sync trigger button (admin only)
- **Admin View**: User management (invite, role assignment, password reset) — admin only
- **State Management**: Pinia store for project/issue/auth state, reactive chart updates via API polling (30s interval)
- **Route Guards**: Vue Router navigation guards redirect unauthenticated users to login

### Layer 2 — API (Slim 4 REST)

| Method | Endpoint | Purpose | Auth | Req |
|--------|----------|---------|------|-----|
| POST | `/api/auth/login` | Authenticate user, start session | Public | ADR-7 |
| POST | `/api/auth/logout` | Destroy session | Any | ADR-7 |
| GET | `/api/auth/me` | Current user info + role | Any | ADR-7 |
| GET | `/api/projects` | List synced GitHub projects | Any | R-001 |
| GET | `/api/projects/{id}` | Project detail with sprint info | Any | R-001 |
| GET | `/api/projects/{id}/issues` | Enhanced issues with time data | Any | R-004 |
| PUT | `/api/issues/{id}/time` | Update estimated/remaining/actual | Member+ | R-004 |
| GET | `/api/projects/{id}/burndown` | Calculated burndown data (ideal + actual curves) | Any | R-005, R-006 |
| GET | `/api/projects/{id}/members` | Member efficiency metrics | Any | R-008 |
| GET | `/api/sync/history` | Sync audit log | Any | R-003 |
| POST | `/api/sync/trigger` | Manual sync trigger | Admin | R-001 |
| GET | `/api/admin/users` | List users | Admin | ADR-7 |
| POST | `/api/admin/users` | Create user | Admin | ADR-7 |

### Layer 3 — Business Logic (Services)
- **`AuthService`** — Login validation via `password_verify()`, session management, role checking. Passwords stored with `password_hash()` (bcrypt, cost 12).
- **`GitHubGraphQLService`** — Builds GraphQL queries, calls `https://api.github.com/graphql` with PAT Bearer token. Single query fetches project + all issues with labels/assignee/iteration. Handles pagination via cursors, rate limit point tracking, error recovery.
- **`BurndownService`** — Calculates ideal curve (total estimated / sprint days) and actual curve (sum of remaining_time per day). Returns arrays of `{date, ideal, actual}` data points.
- **`EfficiencyService`** — Per-member aggregation of estimated vs actual time across completed issues. Calculates accuracy ratio, trend over sprints.
- **`TimeTrackingService`** — Validates time updates, writes to DB, logs change history for audit. Records `changed_by` from authenticated session user.

### Layer 4 — Data Access (Repositories + PDO/MySQL)
- **`UserRepository`** — CRUD for users table, password hash storage, role management
- **`ProjectRepository`** — CRUD for projects table, handles GitHub↔local ID mapping
- **`IssueRepository`** — CRUD for enhanced issues, supports filtering/sorting/pagination
- **`SyncHistoryRepository`** — Insert sync records, query history with date range
- **`TimeLogRepository`** — Append-only log of all time tracking changes

### Layer 5 — Infrastructure
- **MySQL**: cPanel-managed database, created via phpMyAdmin or cPanel MySQL wizard
- **cPanel Cron**: `*/15 * * * * php /home/user/public_html/cron/sync.php` (every 15 min)
- **GitHub Actions Workflow**: Build Vue → SFTP upload → run DB migrations → verify deployment
- **Configuration**: `.env` file for GitHub token, MySQL credentials, project ID, session secret (excluded from git)

---

## Database Schema (MySQL 5.7+ / 8.0)

```sql
-- Users for authentication
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,       -- bcrypt via password_hash()
    role ENUM('admin', 'member') DEFAULT 'member',
    github_username VARCHAR(100),               -- links to team member
    last_login_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Projects synced from GitHub
CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    github_project_id VARCHAR(100) UNIQUE NOT NULL,  -- GraphQL node ID
    github_project_number INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    current_iteration VARCHAR(100),
    release_target VARCHAR(100),
    last_synced_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Issues enhanced with local time tracking
CREATE TABLE issues (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    github_issue_id VARCHAR(100) NOT NULL,      -- GraphQL node ID
    github_issue_number INT NOT NULL,
    project_id INT UNSIGNED NOT NULL,
    title VARCHAR(500) NOT NULL,
    status VARCHAR(50) DEFAULT 'open',
    assignee VARCHAR(100),
    labels JSON,
    iteration VARCHAR(100),
    estimated_time DECIMAL(8,2) DEFAULT 0,
    remaining_time DECIMAL(8,2) DEFAULT 0,
    actual_time DECIMAL(8,2) DEFAULT 0,
    github_created_at DATETIME,
    github_updated_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_issue_project (github_issue_id, project_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit trail for time tracking changes
CREATE TABLE time_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    issue_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(50) NOT NULL,            -- 'estimated_time', 'remaining_time', 'actual_time'
    old_value DECIMAL(8,2),
    new_value DECIMAL(8,2),
    changed_by INT UNSIGNED NOT NULL,           -- FK to users.id
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sync operation records
CREATE TABLE sync_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    snapshot_file VARCHAR(500),                 -- path to JSON snapshot
    issues_added INT DEFAULT 0,
    issues_updated INT DEFAULT 0,
    issues_removed INT DEFAULT 0,
    graphql_points_used INT DEFAULT 0,          -- rate limit points consumed
    status ENUM('completed', 'failed', 'partial') DEFAULT 'completed',
    error_message TEXT,
    synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Daily burndown snapshots (precalculated for chart performance)
CREATE TABLE burndown_daily (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    iteration VARCHAR(100) NOT NULL,
    snapshot_date DATE NOT NULL,
    total_estimated DECIMAL(10,2),
    total_remaining DECIMAL(10,2),
    total_actual DECIMAL(10,2),
    issues_open INT,
    issues_closed INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_burndown (project_id, iteration, snapshot_date),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_issues_project ON issues(project_id);
CREATE INDEX idx_issues_assignee ON issues(assignee);
CREATE INDEX idx_issues_iteration ON issues(iteration);
CREATE INDEX idx_burndown_sprint ON burndown_daily(project_id, iteration);
CREATE INDEX idx_time_logs_issue ON time_logs(issue_id);
CREATE INDEX idx_time_logs_user ON time_logs(changed_by);
```

---

## Project File Structure

```
scrum-dashboard/
├── backend/
│   ├── public/                      # cPanel document root
│   │   ├── index.php                # Slim 4 entry point (all API routes)
│   │   ├── .htaccess                # Apache URL rewrite to index.php
│   │   └── dist/                    # Compiled Vue frontend assets
│   ├── src/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php       # login, logout, me
│   │   │   ├── AdminController.php      # user management (admin only)
│   │   │   ├── ProjectController.php
│   │   │   ├── IssueController.php
│   │   │   ├── BurndownController.php
│   │   │   ├── MemberController.php
│   │   │   └── SyncController.php
│   │   ├── Services/
│   │   │   ├── AuthService.php          # password_verify, session mgmt
│   │   │   ├── GitHubGraphQLService.php # GraphQL v4 queries + cursor pagination
│   │   │   ├── BurndownService.php
│   │   │   ├── EfficiencyService.php
│   │   │   └── TimeTrackingService.php
│   │   ├── Repositories/
│   │   │   ├── UserRepository.php
│   │   │   ├── ProjectRepository.php
│   │   │   ├── IssueRepository.php
│   │   │   ├── SyncHistoryRepository.php
│   │   │   └── TimeLogRepository.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Project.php
│   │   │   ├── Issue.php
│   │   │   └── BurndownPoint.php
│   │   ├── GraphQL/
│   │   │   ├── queries.php              # Named GraphQL query strings
│   │   │   └── ResponseParser.php       # Parse GraphQL responses → domain models
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php       # Session check → 401 if unauthenticated
│   │       ├── AdminMiddleware.php      # Role check → 403 if not admin
│   │       ├── CorsMiddleware.php
│   │       └── JsonResponseMiddleware.php
│   ├── config/
│   │   ├── settings.php              # App configuration (reads .env)
│   │   ├── routes.php                # Route definitions with middleware groups
│   │   └── container.php             # DI container bindings
│   ├── database/
│   │   ├── migrations/               # Numbered migration SQL files
│   │   ├── migrate.php               # Migration runner
│   │   └── seed.php                  # Create initial admin user
│   ├── data/
│   │   └── snapshots/                # Historical sync snapshots (JSON)
│   ├── cron/
│   │   └── sync.php                  # Cron entry point for GitHub GraphQL sync
│   ├── composer.json
│   └── .env.example
├── frontend/
│   ├── src/
│   │   ├── App.vue
│   │   ├── main.js
│   │   ├── router/
│   │   │   └── index.js              # Vue Router with auth guards
│   │   ├── views/
│   │   │   ├── LoginView.vue         # Login form
│   │   │   ├── DashboardView.vue     # Burndown chart + sprint health
│   │   │   ├── IssuesView.vue        # Issue list with time editing
│   │   │   ├── MembersView.vue       # Efficiency analysis charts
│   │   │   ├── SyncView.vue          # Sync status and history
│   │   │   └── AdminView.vue         # User management (admin only)
│   │   ├── components/
│   │   │   ├── BurndownChart.vue     # Chart.js line chart wrapper
│   │   │   ├── EfficiencyChart.vue   # Chart.js bar chart wrapper
│   │   │   ├── IssueTimeEditor.vue   # Inline time editing component
│   │   │   ├── SprintSelector.vue
│   │   │   └── SyncStatus.vue
│   │   ├── services/
│   │   │   └── api.js                # Axios API client with session cookie
│   │   └── stores/
│   │       ├── authStore.js          # Pinia store for auth + user state
│   │       ├── projectStore.js       # Pinia store for project state
│   │       └── dashboardStore.js     # Pinia store for chart data
│   ├── package.json
│   └── vite.config.js                # Build output → backend/public/dist/
├── .github/
│   └── workflows/
│       └── deploy.yml                # Build + SFTP deploy to cPanel
├── .env.example
└── README.md
```

---

## Implementation Steps

### Phase 1: Foundation — Backend skeleton + Auth + DB
1. Initialize PHP project with `composer require slim/slim slim/psr7 vlucas/phpdotenv`
2. Create MySQL database via cPanel, write migration SQL files (6 tables: users, projects, issues, time_logs, sync_history, burndown_daily)
3. Build migration runner (`database/migrate.php`) and seed script (`database/seed.php` — creates initial admin user with bcrypt password)
4. Set up Slim 4 entry point with routing, DI container, MySQL PDO connection (from `.env`), CORS middleware, JSON response middleware
5. Implement `AuthService`, `AuthController`, `AuthMiddleware`, `AdminMiddleware` — login/logout/me endpoints, session-based cookie auth, role checking
6. Create `.htaccess` for Apache URL rewriting

### Phase 2: GitHub GraphQL Integration *(depends on Phase 1)*
7. Write GraphQL query templates in `src/GraphQL/queries.php` — project query with issues (title, status, assignee, labels, iteration), cursor-based pagination
8. Implement `GitHubGraphQLService` — sends POST to `https://api.github.com/graphql` with Bearer PAT, handles cursor pagination, rate limit point tracking, retries on transient errors
9. Implement `ResponseParser` — transforms GraphQL JSON response into local domain models (Project, Issue arrays)
10. Build sync logic: query GraphQL → parse response → diff with local DB → upsert issues → write JSON snapshot → log sync_history with `graphql_points_used`
11. Create `cron/sync.php` entry point (reads `.env`, runs sync standalone outside Slim context)
12. Test sync with real GitHub project — verify all issues synced, labels/iteration/assignee mapped correctly

### Phase 3: Analytics Engine *(depends on Phase 1, parallel with Phase 2)*
13. Implement `BurndownService` — ideal curve calculation (total_estimated ÷ sprint_working_days), actual curve from `burndown_daily` table
14. Build daily burndown snapshot job (runs after each sync, captures current remaining_time totals into `burndown_daily`)
15. Implement `EfficiencyService` — per-member estimated vs actual aggregation, accuracy ratio, historical trend
16. Implement `TimeTrackingService` with audit logging to `time_logs` table (FK to `users.id` for `changed_by`)
17. Create `BurndownController`, `MemberController`, `IssueController` API endpoints (all behind `AuthMiddleware`)

### Phase 4: Frontend Dashboard *(depends on Phase 2 + 3)*
18. Initialize Vue 3 + Vite project, configure build output to `backend/public/dist/`
19. Build `LoginView` with email/password form, Axios `withCredentials: true` for session cookies
20. Build `authStore` (Pinia) — login/logout actions, `me` endpoint polling, role state
21. Configure Vue Router with navigation guards (redirect to `/login` if unauthenticated, restrict `/admin` to admin role)
22. Build `DashboardView` with `BurndownChart` component (Chart.js line chart with 2 curves)
23. Build `IssuesView` with `IssueTimeEditor` for inline time updates
24. Build `MembersView` with `EfficiencyChart` (grouped bar: estimated vs actual per member)
25. Build `SyncView` with status display + manual sync trigger (admin only)
26. Build `AdminView` for user management — list users, create new user, assign roles
27. Implement Pinia stores + API service layer with 30-second polling

### Phase 5: Deployment Pipeline *(parallel with Phase 4)*
28. Create GitHub Actions workflow: install deps → build Vue → SFTP to cPanel → run `php migrate.php`
29. Configure cPanel cron for `sync.php` (every 15 min)
30. Write `.env.example` with all config vars (MySQL creds, GitHub PAT, session secret, project ID)
31. Write deployment README with cPanel setup instructions (create MySQL DB, configure cron, SFTP secrets)

### Phase 6: Polish & Validation *(depends on all above)*
32. End-to-end: login → sync → update time → verify burndown chart accuracy
33. Performance: all API endpoints < 200ms
34. Security review: password hashing, session fixation protection, CSRF mitigation, SQL injection prevention (parameterized queries), input validation
35. Error handling: GraphQL failures, rate limits, MySQL connection errors, concurrent access
36. Code documentation and inline comments

---

## Verification

1. **Authentication**: Login with valid creds → session created → API accessible. Invalid creds → 401. Logout → session destroyed → API returns 401. Admin-only endpoints return 403 for member role.
2. **Sync accuracy**: Sync a real GitHub project via GraphQL, verify all issues appear with correct metadata (labels, iteration, assignee)
3. **GraphQL efficiency**: Verify single GraphQL query fetches project + all issues (inspect `graphql_points_used` in sync_history). Confirm cursor pagination works for >100 issues.
4. **Time tracking**: Update remaining_time as authenticated user, verify burndown chart updates within 30 seconds, verify `time_logs` records `changed_by` as user ID
5. **Burndown correctness**: Compare hand-calculated burndown for known dataset vs chart output
6. **Efficiency reports**: Complete sprint with known estimate/actual pairs, verify member accuracy percentages
7. **Historical snapshots**: Run 3+ syncs, verify JSON snapshot files exist and contain correct data
8. **Performance**: All dashboard API endpoints < 200ms (browser DevTools)
9. **Deploy pipeline**: Push to main → Actions succeeds (build + SFTP + migrate) → app loads on cPanel
10. **Concurrency**: 3+ authenticated users update time simultaneously → no data corruption (MySQL row-level locking)
11. **Security**: Verify passwords are bcrypt hashed in DB, sessions have `httpOnly`+`secure` flags, all API inputs are parameterized (no SQL injection)

---

## Key Decisions

| Decision | Choice | Over | Reason |
|----------|--------|------|--------|
| Backend | PHP 8.2 + Slim 4 | Laravel, raw PHP | Lightweight yet maintainable |
| Database | MySQL | SQLite | Better concurrency for 6-15 users, cPanel native |
| GitHub API | GraphQL v4 | REST v3 | Single query per sync, cursor pagination, point-based rate limits |
| Auth | Session-based | JWT, GitHub OAuth | Simpler, no token refresh, works on any PHP host |
| Roles | admin / member | Single role | Admin controls sync + users; member updates time + views |
| Frontend | Vue 3 + Chart.js | React, Alpine.js | Reactive SPA with mature charting, small bundle |
| Sync | Cron every 15 min | Webhooks | Shared hosting can't listen for incoming hooks |
| Snapshots | JSON files | DB BLOBs | Inspectable, easy backup, no DB bloat |
| Burndown | Precalculated daily | Real-time calc | Dashboard loads in < 200ms |

## Further Considerations

1. **Session storage**: PHP file-based sessions work for single shared hosting server. If scaling to multiple servers in future, switch to MySQL session handler.
2. **CSRF protection**: For v1, session cookie + same-origin API calls (SPA served from same domain) mitigates CSRF. Consider adding CSRF tokens if the threat model changes.
3. **GraphQL subscription**: GitHub doesn't support GraphQL subscriptions, so cron polling remains the only option for shared hosting. If moving to VPS later, could explore webhooks for near-real-time sync.

---

**Requirements Traceability**:
- R-001, R-002 → ADR-4 (GitHub GraphQL sync)
- R-003 → ADR-5 (JSON snapshots) + sync_history table
- R-004 → EnhancedIssue (estimated_time, remaining_time, actual_time)
- R-005, R-006 → ADR-3 (Chart.js burndown) + BurndownService + burndown_daily
- R-007 → Dashboard View + BurndownChart component
- R-008 → EfficiencyService + MembersView
- R-009 → ADR-6 (SFTP deploy to cPanel)
- R-010 → ADR-6 (GitHub Actions CI/CD)
- R-011 → ADR-2 (MySQL on cPanel, no heavy infra)
- R-012 → ADR-1 (Slim 4 clean code) + ADR-3 (Vue 3 maintainable frontend)