# Task Breakdown Structure

## Project Overview
- **Project**: PRJ-01 — Scrum Master Assistant (GitHub-Integrated Scrum Dashboard)
- **Total Tasks**: 36
- **Phases**: 6 (+ Phase 0 completed)
- **Critical Path**: Phase 1 → Phase 2 → Phase 4 → Phase 6
- **Parallel Tracks**: Phase 2 ∥ Phase 3, Phase 4 ∥ Phase 5

## Phase Summary

| Phase | Name | Tasks | Dependencies | Effort (days) |
|-------|------|-------|-------------|---------------|
| 0 | Requirements & Architecture | — | — | ✅ Complete |
| 1 | Foundation (Backend + Auth + DB) | T001–T006 | — | 5 |
| 2 | GitHub GraphQL Integration | T007–T012 | Phase 1 | 5 |
| 3 | Analytics Engine | T013–T017 | Phase 1 (∥ Phase 2) | 4 |
| 4 | Frontend Dashboard | T018–T027 | Phase 2 + 3 | 7 |
| 5 | Deployment Pipeline | T028–T031 | Phase 1 (∥ Phase 4) | 3 |
| 6 | Polish & Validation | T032–T036 | All above | 3 |
| | **Total** | **36** | | **~27 days** |

**With parallelization (Phase 2 ∥ 3, Phase 4 ∥ 5)**: ~20 working days calendar time

---

## Phase 0: Requirements & Architecture ✅ Complete

Completed on 2026-04-02:
- Requirements document translated and structured (12 requirements)
- Goals extraction with success criteria and KPIs
- Domain concept analysis (8 entities, 5 business concepts)
- Collaboration diagrams (8 diagrams)
- Technical architecture approved (7 ADRs)

---

## Phase 1: Foundation — Backend Skeleton + Auth + DB

**Description**: Initialize the PHP backend project, create MySQL schema, implement authentication, and establish the core API framework.  
**Dependencies**: None  
**Estimated Duration**: 5 days  
**Delivers**: Working authenticated API skeleton with database

---

### T001 — Initialize PHP Backend Project
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Set up PHP 8.2 project with Composer, install Slim 4 framework and dependencies. Create project directory structure per technical architecture.

**Acceptance Criteria**:
- [ ] `composer.json` created with `slim/slim`, `slim/psr7`, `vlucas/phpdotenv`
- [ ] Directory structure matches architecture spec (Controllers/, Services/, Repositories/, Models/, GraphQL/, Middleware/)
- [ ] `composer install` runs without errors
- [ ] `.env.example` created with all required config variables

**Dependencies**: None  
**Deliverables**: `backend/composer.json`, directory skeleton, `.env.example`  
**Source Requirements**: R-009, R-012  

---

### T002 — Create MySQL Database Schema & Migrations
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Write SQL migration files for all 6 tables (users, projects, issues, time_logs, sync_history, burndown_daily) with proper constraints, indexes, and foreign keys. Create migration runner script.

**Acceptance Criteria**:
- [ ] Migration files create all 6 tables with correct column types (DECIMAL for time, JSON for labels, ENUM for roles/status)
- [ ] All foreign key constraints defined (ON DELETE CASCADE where appropriate)
- [ ] All indexes created (project_id, assignee, iteration, burndown composite, time_logs)
- [ ] `migrate.php` runner executes migrations idempotently (skips already-applied)
- [ ] Migrations run successfully on MySQL 5.7+ and 8.0

**Dependencies**: T001  
**Deliverables**: `database/migrations/*.sql`, `database/migrate.php`  
**Source Requirements**: R-003, R-004, R-011  

---

### T003 — Create Database Seed Script
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create seed script that initializes the first admin user with bcrypt-hashed password. Ensure idempotent execution (skip if admin exists).

**Acceptance Criteria**:
- [ ] `seed.php` creates admin user with `password_hash()` (bcrypt, cost 12)
- [ ] Seed is idempotent — does not duplicate if run multiple times
- [ ] Admin email and password configurable via `.env` or CLI arguments
- [ ] Outputs confirmation message on success

**Dependencies**: T002  
**Deliverables**: `database/seed.php`  
**Source Requirements**: ADR-7  

---

### T004 — Set Up Slim 4 Entry Point & Core Middleware
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Create the Slim 4 application entry point (`index.php`), configure DI container with MySQL PDO connection, register CORS and JSON response middleware, set up Apache `.htaccess` rewriting.

**Acceptance Criteria**:
- [ ] `public/index.php` bootstraps Slim 4 app with DI container
- [ ] `config/container.php` registers PDO (MySQL) connection from `.env` variables
- [ ] `config/settings.php` reads all `.env` config values
- [ ] `CorsMiddleware` handles preflight OPTIONS and sets proper headers
- [ ] `JsonResponseMiddleware` ensures all API responses are JSON with correct Content-Type
- [ ] `.htaccess` rewrites all non-file requests to `index.php`
- [ ] `GET /api/health` returns `{"status":"ok"}` (smoke test)

**Dependencies**: T001  
**Deliverables**: `public/index.php`, `public/.htaccess`, `config/settings.php`, `config/container.php`, `src/Middleware/CorsMiddleware.php`, `src/Middleware/JsonResponseMiddleware.php`  
**Source Requirements**: R-009, R-012  

---

### T005 — Implement Authentication System
**Category**: Development | **Priority**: High | **Effort**: 1.5 days

**Description**: Build session-based authentication with login/logout/me endpoints, auth middleware for protected routes, and admin role middleware.

**Acceptance Criteria**:
- [ ] `AuthService` validates login with `password_verify()`, creates PHP session, sets httpOnly + secure cookie flags
- [ ] `AuthController` provides `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- [ ] `AuthMiddleware` returns 401 for unauthenticated requests on protected routes
- [ ] `AdminMiddleware` returns 403 for non-admin users on admin routes
- [ ] Session fixation protection: regenerate session ID on login
- [ ] `UserRepository` provides `findByEmail()`, `findById()`, `create()` with parameterized queries
- [ ] Login returns user info (id, email, display_name, role) — never returns password_hash
- [ ] Invalid credentials return 401 with generic error message (no user enumeration)

**Dependencies**: T002, T003, T004  
**Deliverables**: `src/Services/AuthService.php`, `src/Controllers/AuthController.php`, `src/Middleware/AuthMiddleware.php`, `src/Middleware/AdminMiddleware.php`, `src/Repositories/UserRepository.php`, `src/Models/User.php`  
**Source Requirements**: ADR-7  
**Risk Factors**:
- Risk: Session cookie not sent cross-origin in dev → Mitigation: Configure Vite proxy for development

---

### T006 — Configure Route Definitions
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Define all API route groups in `config/routes.php` with appropriate middleware assignment. Public routes (auth/login), authenticated routes (projects, issues, burndown), and admin routes (sync trigger, user management).

**Acceptance Criteria**:
- [ ] `config/routes.php` defines all 13 API endpoints per architecture spec
- [ ] Public group: `/api/auth/login`
- [ ] Authenticated group (AuthMiddleware): all `/api/projects/*`, `/api/issues/*`, `/api/sync/history`, `/api/auth/logout`, `/api/auth/me`
- [ ] Admin group (AdminMiddleware): `/api/sync/trigger`, `/api/admin/users`
- [ ] Routes resolve correctly — verified with curl/Postman on health and auth endpoints

**Dependencies**: T004, T005  
**Deliverables**: `config/routes.php`  
**Source Requirements**: R-012  

---

## Phase 2: GitHub GraphQL Integration

**Description**: Implement the GitHub GraphQL v4 client, sync service, response parsing, snapshot generation, and cron entry point.  
**Dependencies**: Phase 1  
**Estimated Duration**: 5 days  
**Delivers**: Working GitHub sync pulling real project/issue data into MySQL

---

### T007 — Write GraphQL Query Templates
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create named GraphQL query strings for fetching GitHub project data with all required fields. Support cursor-based pagination for issues.

**Acceptance Criteria**:
- [ ] `src/GraphQL/queries.php` contains named query for project + issues
- [ ] Query fetches: project name, description, number; issues with title, status, assignee login, labels, iteration title, created/updated dates
- [ ] Pagination via `after` cursor parameter, fetches 100 items per page
- [ ] Query uses `$owner`, `$repo`, `$projectNumber`, `$cursor` variables

**Dependencies**: T001  
**Deliverables**: `src/GraphQL/queries.php`  
**Source Requirements**: R-001, ADR-4  

---

### T008 — Implement GitHubGraphQLService
**Category**: Development | **Priority**: High | **Effort**: 1.5 days

**Description**: Build the core service that communicates with GitHub's GraphQL API. Handles authentication, cursor pagination, rate limit tracking, retries, and error handling.

**Acceptance Criteria**:
- [ ] Sends POST to `https://api.github.com/graphql` with Bearer PAT from `.env`
- [ ] Handles cursor-based pagination — loops until `hasNextPage` is false
- [ ] Tracks rate limit points from response headers (`X-RateLimit-Remaining`)
- [ ] Retries on transient HTTP errors (502, 503) with exponential backoff (max 3 retries)
- [ ] Returns structured array of project metadata + issues
- [ ] Throws typed exceptions for auth failures (401) and rate limit exceeded (403)

**Dependencies**: T007  
**Deliverables**: `src/Services/GitHubGraphQLService.php`  
**Source Requirements**: R-001, R-002, ADR-4  
**Risk Factors**:
- Risk: GitHub API schema changes → Mitigation: Pin query to known working fields, add response validation

---

### T009 — Implement GraphQL ResponseParser
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Transform raw GraphQL JSON responses into local domain model objects (Project, Issue arrays) suitable for database persistence.

**Acceptance Criteria**:
- [ ] Parses nested GraphQL response into flat `Project` and `Issue` model arrays
- [ ] Maps GraphQL node IDs to `github_project_id` / `github_issue_id`
- [ ] Extracts labels as JSON array, iteration title as string, assignee login as string
- [ ] Handles missing/null fields gracefully (e.g., unassigned issues)

**Dependencies**: T007  
**Deliverables**: `src/GraphQL/ResponseParser.php`, `src/Models/Project.php`, `src/Models/Issue.php`  
**Source Requirements**: R-001, R-004  

---

### T010 — Build Sync Logic with Diff & Snapshot
**Category**: Development | **Priority**: High | **Effort**: 1.5 days

**Description**: Implement the core sync process: fetch from GraphQL, compare with local DB, upsert changes, write JSON snapshot, and log to sync_history.

**Acceptance Criteria**:
- [ ] Compares fetched issues against local DB by `github_issue_id`
- [ ] Inserts new issues, updates changed issues (title, status, assignee, labels, iteration, github_updated_at)
- [ ] Preserves local time tracking fields (estimated_time, remaining_time, actual_time) during sync — never overwrites
- [ ] Writes JSON snapshot to `data/snapshots/YYYY-MM-DD_HH-mm.json`
- [ ] Logs sync_history record with issues_added, issues_updated, graphql_points_used, status
- [ ] Wraps DB operations in MySQL transaction — rollback on failure
- [ ] `SyncController` provides `POST /api/sync/trigger` (admin only) and `GET /api/sync/history`

**Dependencies**: T008, T009, T002  
**Deliverables**: `src/Services/GitHubSyncService.php` (sync orchestrator), `src/Repositories/ProjectRepository.php`, `src/Repositories/IssueRepository.php`, `src/Repositories/SyncHistoryRepository.php`, `src/Controllers/SyncController.php`  
**Source Requirements**: R-001, R-002, R-003, R-004  

---

### T011 — Create Cron Sync Entry Point
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create standalone PHP script for cPanel cron execution that runs the sync process outside of the Slim HTTP context.

**Acceptance Criteria**:
- [ ] `cron/sync.php` bootstraps minimal environment: loads `.env`, creates PDO, instantiates services
- [ ] Runs sync for configured project ID from `.env`
- [ ] Logs output to `data/sync.log` (append mode, timestamped)
- [ ] Exits with code 0 on success, non-zero on failure
- [ ] Handles lock file (`data/sync.lock`) to prevent overlapping cron executions

**Dependencies**: T010  
**Deliverables**: `cron/sync.php`  
**Source Requirements**: R-001, R-002, ADR-4  

---

### T012 — Integration Test: GitHub Sync End-to-End
**Category**: Testing | **Priority**: High | **Effort**: 0.5 days

**Description**: Test the complete sync pipeline against a real GitHub project. Verify data accuracy, snapshot creation, and sync history logging.

**Acceptance Criteria**:
- [ ] Sync runs successfully against a real GitHub project with 10+ issues
- [ ] All issues appear in local DB with correct title, status, assignee, labels, iteration
- [ ] JSON snapshot file created with correct content
- [ ] sync_history record logged with accurate counts
- [ ] Running sync twice produces no duplicates (idempotent upsert)
- [ ] Local time tracking fields preserved across syncs

**Dependencies**: T010, T011  
**Deliverables**: Test results log, any bug fixes  
**Source Requirements**: R-001, R-002, R-003  

---

## Phase 3: Analytics Engine (Parallel with Phase 2)

**Description**: Implement burndown calculation, efficiency analysis, time tracking with audit, and corresponding API endpoints.  
**Dependencies**: Phase 1 (can run parallel with Phase 2)  
**Estimated Duration**: 4 days  
**Delivers**: Working analytics APIs for burndown charts and member efficiency

---

### T013 — Implement BurndownService
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Build the service that calculates ideal and actual burndown curves for a given project iteration.

**Acceptance Criteria**:
- [ ] Ideal curve: total_estimated ÷ sprint_working_days → linear decrease to zero
- [ ] Actual curve: reads from `burndown_daily` table → array of `{date, remaining}` points
- [ ] Returns JSON array of `{date, ideal, actual}` data points for Chart.js consumption
- [ ] Handles edge cases: no data yet, mid-sprint start, sprint without estimates
- [ ] `BurndownController` provides `GET /api/projects/{id}/burndown?iteration=X`

**Dependencies**: T002, T004, T006  
**Deliverables**: `src/Services/BurndownService.php`, `src/Controllers/BurndownController.php`, `src/Models/BurndownPoint.php`  
**Source Requirements**: R-005, R-006, R-007  

---

### T014 — Build Daily Burndown Snapshot Job
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create a routine that captures the current day's remaining_time totals into the `burndown_daily` table. Runs after each sync and can be called independently.

**Acceptance Criteria**:
- [ ] Aggregates `SUM(remaining_time)`, `SUM(estimated_time)`, `SUM(actual_time)`, `COUNT(open)`, `COUNT(closed)` per iteration
- [ ] Inserts or updates `burndown_daily` for current date (UPSERT via `ON DUPLICATE KEY UPDATE`)
- [ ] Called automatically at end of sync process (T010)
- [ ] Can also be triggered independently via `BurndownService::captureDaily()`

**Dependencies**: T002, T013  
**Deliverables**: Method in `BurndownService`, integration hook in sync pipeline  
**Source Requirements**: R-005, R-006  

---

### T015 — Implement EfficiencyService
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Build per-member efficiency analysis: estimated vs actual time aggregation, accuracy ratio calculation, and historical trend data.

**Acceptance Criteria**:
- [ ] Aggregates per assignee: total estimated_time, total actual_time, count of completed issues
- [ ] Calculates accuracy ratio: `actual / estimated` (1.0 = perfect, >1.0 = underestimated, <1.0 = overestimated)
- [ ] Historical trend: accuracy ratio per sprint for trend analysis
- [ ] `MemberController` provides `GET /api/projects/{id}/members?iteration=X` (optional iteration filter)
- [ ] Returns JSON array of `{member, estimated, actual, ratio, issues_count}` + trend data

**Dependencies**: T002, T004, T006  
**Deliverables**: `src/Services/EfficiencyService.php`, `src/Controllers/MemberController.php`  
**Source Requirements**: R-008  

---

### T016 — Implement TimeTrackingService with Audit
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Build the service for updating time fields on issues with full audit trail logging.

**Acceptance Criteria**:
- [ ] `PUT /api/issues/{id}/time` accepts `{estimated_time, remaining_time, actual_time}` — partial updates allowed
- [ ] Validates input: non-negative numbers, reasonable range (0–9999.99)
- [ ] Reads current values, writes `time_logs` entry with old_value, new_value, changed_by (from session user ID)
- [ ] Updates `issues` table with new values and `updated_at` timestamp
- [ ] Wraps read+write in transaction for consistency
- [ ] `IssueController` provides `GET /api/projects/{id}/issues` with filtering (assignee, iteration, status)
- [ ] `ProjectController` provides `GET /api/projects` and `GET /api/projects/{id}`

**Dependencies**: T002, T005, T006  
**Deliverables**: `src/Services/TimeTrackingService.php`, `src/Controllers/IssueController.php`, `src/Controllers/ProjectController.php`, `src/Repositories/TimeLogRepository.php`  
**Source Requirements**: R-004, R-006  

---

### T017 — Implement Admin User Management Endpoints
**Category**: Development | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Build admin-only endpoints for listing and creating users.

**Acceptance Criteria**:
- [ ] `GET /api/admin/users` returns list of users (id, email, display_name, role, github_username, last_login_at) — never returns password_hash
- [ ] `POST /api/admin/users` creates user with `{email, display_name, password, role, github_username}`
- [ ] Password hashed with `password_hash()` before storage
- [ ] Email uniqueness enforced — returns 409 on duplicate
- [ ] Both endpoints behind `AdminMiddleware`

**Dependencies**: T005, T006  
**Deliverables**: `src/Controllers/AdminController.php`  
**Source Requirements**: ADR-7  

---

## Phase 4: Frontend Dashboard

**Description**: Build the Vue 3 SPA with all views, components, stores, and API integration.  
**Dependencies**: Phase 2 + Phase 3  
**Estimated Duration**: 7 days  
**Delivers**: Complete web dashboard with login, burndown charts, issue management, and admin panel

---

### T018 — Initialize Vue 3 + Vite Project
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Scaffold the frontend project with Vue 3, Vite, Vue Router, Pinia, Chart.js, and Axios. Configure build output to `backend/public/dist/`.

**Acceptance Criteria**:
- [ ] `package.json` with vue@3, vue-router@4, pinia, chart.js@4, axios
- [ ] `vite.config.js` outputs build to `../backend/public/dist/`
- [ ] Dev server proxy configured to forward `/api/*` to PHP backend (localhost:8080 or similar)
- [ ] `npm run build` produces working static assets
- [ ] Basic `App.vue` with `<router-view>` renders

**Dependencies**: Phase 2, Phase 3 (APIs must be functional)  
**Deliverables**: `frontend/` project skeleton  
**Source Requirements**: R-012, ADR-3  

---

### T019 — Build Login View
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create login page with email/password form and session-based authentication.

**Acceptance Criteria**:
- [ ] `LoginView.vue` with email + password input fields and submit button
- [ ] Axios configured with `withCredentials: true` for session cookies
- [ ] On success: redirect to Dashboard; on failure: display error message
- [ ] No password stored in frontend state — only session cookie
- [ ] Prevent form resubmission (disable button during request)

**Dependencies**: T018  
**Deliverables**: `frontend/src/views/LoginView.vue`  
**Source Requirements**: ADR-7  

---

### T020 — Build Auth Store & Route Guards
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Implement Pinia auth store and Vue Router navigation guards for access control.

**Acceptance Criteria**:
- [ ] `authStore` — `login()`, `logout()`, `fetchMe()` actions; `user`, `isAuthenticated`, `isAdmin` getters
- [ ] On app load: calls `GET /api/auth/me` to restore session state
- [ ] Vue Router `beforeEach` guard: redirects to `/login` if not authenticated (except login route)
- [ ] Admin routes (`/admin`) restricted — redirects non-admin to dashboard
- [ ] Logout clears store state and redirects to login

**Dependencies**: T019  
**Deliverables**: `frontend/src/stores/authStore.js`, router guard in `frontend/src/router/index.js`  
**Source Requirements**: ADR-7  

---

### T021 — Build API Service Layer
**Category**: Development | **Priority**: High | **Effort**: 0.5 days

**Description**: Create centralized API client with Axios instance, error interceptors, and typed API methods.

**Acceptance Criteria**:
- [ ] `api.js` creates Axios instance with `baseURL`, `withCredentials: true`
- [ ] 401 response interceptor triggers logout + redirect to login
- [ ] Named methods: `getProjects()`, `getIssues(projectId)`, `updateIssueTime(issueId, data)`, `getBurndown(projectId, iteration)`, `getMembers(projectId)`, `getSyncHistory()`, `triggerSync()`, `getUsers()`, `createUser(data)`
- [ ] All methods return parsed response data (unwrap Axios response)

**Dependencies**: T018  
**Deliverables**: `frontend/src/services/api.js`  
**Source Requirements**: R-012  

---

### T022 — Build Dashboard View with Burndown Chart
**Category**: Development | **Priority**: High | **Effort**: 1.5 days

**Description**: Build the main dashboard page with sprint selector and burndown chart (ideal vs actual curves).

**Acceptance Criteria**:
- [ ] `DashboardView.vue` displays sprint selector dropdown and burndown chart
- [ ] `SprintSelector.vue` loads iterations from project data, defaults to current
- [ ] `BurndownChart.vue` renders Chart.js line chart with two datasets: ideal (dashed blue) and actual (solid red)
- [ ] Chart axes: X = dates, Y = hours remaining
- [ ] Sprint health indicator: "On Track" (actual ≤ ideal), "At Risk" (actual > ideal by <20%), "Behind" (>20%)
- [ ] `dashboardStore` fetches burndown data, supports 30-second auto-refresh
- [ ] Chart is responsive and readable on desktop screens

**Dependencies**: T021  
**Deliverables**: `frontend/src/views/DashboardView.vue`, `frontend/src/components/BurndownChart.vue`, `frontend/src/components/SprintSelector.vue`, `frontend/src/stores/dashboardStore.js`  
**Source Requirements**: R-005, R-006, R-007  

---

### T023 — Build Issues View with Time Editor
**Category**: Development | **Priority**: High | **Effort**: 1.5 days

**Description**: Build the issues management page with sortable/filterable table and inline time editing.

**Acceptance Criteria**:
- [ ] `IssuesView.vue` displays table: issue #, title, assignee, status, iteration, estimated, remaining, actual
- [ ] Filter controls: by assignee (dropdown), by iteration (dropdown), by status (open/closed/all)
- [ ] Sortable columns: click header to sort ascending/descending
- [ ] `IssueTimeEditor.vue` — inline editable fields for estimated, remaining, actual time
- [ ] On blur/enter: calls `PUT /api/issues/{id}/time` to save; shows success/error feedback
- [ ] `projectStore` manages issues list state with filtering/sorting
- [ ] Shows total hours row at bottom (sum of estimated, remaining, actual)

**Dependencies**: T021  
**Deliverables**: `frontend/src/views/IssuesView.vue`, `frontend/src/components/IssueTimeEditor.vue`, `frontend/src/stores/projectStore.js`  
**Source Requirements**: R-004, R-006  

---

### T024 — Build Members View with Efficiency Charts
**Category**: Development | **Priority**: High | **Effort**: 1 day

**Description**: Build the member efficiency analysis page with bar charts and accuracy metrics.

**Acceptance Criteria**:
- [ ] `MembersView.vue` displays grouped bar chart (estimated vs actual per member) and accuracy table
- [ ] `EfficiencyChart.vue` renders Chart.js grouped bar chart: blue bars = estimated, orange bars = actual
- [ ] Table shows: member name, total estimated, total actual, accuracy ratio, issues completed
- [ ] Iteration filter: view efficiency for specific sprint or all-time
- [ ] Color coding: ratio 0.9–1.1 green (accurate), <0.9 blue (overestimated), >1.1 red (underestimated)

**Dependencies**: T021  
**Deliverables**: `frontend/src/views/MembersView.vue`, `frontend/src/components/EfficiencyChart.vue`  
**Source Requirements**: R-008  

---

### T025 — Build Sync Status View
**Category**: Development | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Build the sync monitoring page showing last sync status, history log, and manual sync trigger.

**Acceptance Criteria**:
- [ ] `SyncView.vue` displays: last sync timestamp, sync status badge, GraphQL points used
- [ ] Sync history table: date, issues added/updated/removed, points used, status
- [ ] `SyncStatus.vue` component shows live status indicator (green = recent sync, yellow = >30 min ago, red = failed)
- [ ] Manual "Sync Now" button — visible only to admin role
- [ ] Button shows loading spinner during sync, success/error feedback after

**Dependencies**: T021, T020  
**Deliverables**: `frontend/src/views/SyncView.vue`, `frontend/src/components/SyncStatus.vue`  
**Source Requirements**: R-001, R-003  

---

### T026 — Build Admin View (User Management)
**Category**: Development | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Build admin-only page for managing dashboard users.

**Acceptance Criteria**:
- [ ] `AdminView.vue` accessible only to admin role (route guard + UI hide)
- [ ] Displays user list table: email, display name, role, GitHub username, last login
- [ ] "Add User" form: email, display name, password, role selector, GitHub username
- [ ] Form validation: required fields, valid email format, password minimum 8 chars
- [ ] Success: new user appears in list; Error: display message (e.g., duplicate email)

**Dependencies**: T020, T021  
**Deliverables**: `frontend/src/views/AdminView.vue`  
**Source Requirements**: ADR-7  

---

### T027 — Implement Auto-Refresh & Polling
**Category**: Development | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Add 30-second polling to dashboard and issues views for near-real-time updates.

**Acceptance Criteria**:
- [ ] Dashboard burndown chart auto-refreshes every 30 seconds
- [ ] Issues view refreshes after successful time update (immediate) and every 60 seconds (background)
- [ ] Polling stops when user navigates away (cleanup in `onUnmounted`)
- [ ] No redundant requests if data hasn't changed (ETag or last-modified check optional for v1)
- [ ] Visual indicator when data is refreshing (subtle loading spinner)

**Dependencies**: T022, T023  
**Deliverables**: Polling logic in `dashboardStore.js` and `projectStore.js`  
**Source Requirements**: R-006  

---

## Phase 5: Deployment Pipeline (Parallel with Phase 4)

**Description**: Set up CI/CD, cron job, and deployment documentation.  
**Dependencies**: Phase 1 (can run parallel with Phase 4)  
**Estimated Duration**: 3 days  
**Delivers**: Automated deployment to cPanel and scheduled sync

---

### T028 — Create GitHub Actions Deploy Workflow
**Category**: DevOps | **Priority**: High | **Effort**: 1 day

**Description**: Write GitHub Actions workflow that builds the Vue frontend and deploys everything to cPanel via SFTP.

**Acceptance Criteria**:
- [ ] `.github/workflows/deploy.yml` triggers on push to `main`
- [ ] Steps: checkout → install Node deps → `npm run build` → install Composer deps → SFTP upload to cPanel
- [ ] SFTP credentials stored as GitHub repository secrets (`SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD`)
- [ ] Uploads: `backend/public/`, `backend/src/`, `backend/config/`, `backend/vendor/`, `backend/cron/`, `backend/database/`
- [ ] Post-deploy: runs `php migrate.php` via SSH or HTTP trigger
- [ ] Excludes: `.env`, `data/snapshots/`, `database/*.sqlite` (if any)

**Dependencies**: T001, T004  
**Deliverables**: `.github/workflows/deploy.yml`  
**Source Requirements**: R-009, R-010, ADR-6  
**Risk Factors**:
- Risk: SFTP connection timeout on large uploads → Mitigation: Use incremental sync (lftp mirror), exclude unchanged vendor/

---

### T029 — Configure cPanel Cron Job
**Category**: DevOps | **Priority**: High | **Effort**: 0.5 days

**Description**: Document and configure the cPanel cron job for automated GitHub sync.

**Acceptance Criteria**:
- [ ] Cron command: `*/15 * * * * php /home/user/public_html/cron/sync.php >> /home/user/logs/sync.log 2>&1`
- [ ] Tested: cron executes successfully, sync.log shows output
- [ ] Lock file mechanism prevents overlapping runs (from T011)

**Dependencies**: T011  
**Deliverables**: Cron configuration documentation, verification log  
**Source Requirements**: R-001, R-002, ADR-4  

---

### T030 — Write Environment Configuration Template
**Category**: Documentation | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Create comprehensive `.env.example` with all configuration variables and documentation.

**Acceptance Criteria**:
- [ ] `.env.example` contains all variables with descriptive comments
- [ ] Variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`, `GITHUB_PAT`, `GITHUB_OWNER`, `GITHUB_REPO`, `GITHUB_PROJECT_NUMBER`, `SESSION_SECRET`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `APP_ENV`, `APP_URL`
- [ ] Sensitive defaults are placeholders (not real values)
- [ ] Clear instructions in comments for obtaining GitHub PAT

**Dependencies**: T001  
**Deliverables**: `.env.example`  
**Source Requirements**: R-012  

---

### T031 — Write Deployment Guide
**Category**: Documentation | **Priority**: Medium | **Effort**: 1 day

**Description**: Write comprehensive README with setup and deployment instructions.

**Acceptance Criteria**:
- [ ] `README.md` covers: project overview, tech stack, prerequisites, local development setup, cPanel deployment steps
- [ ] cPanel setup section: create MySQL DB, upload files, configure `.env`, run migrations, run seed, set up cron
- [ ] GitHub Actions setup: required secrets, workflow behavior, manual trigger instructions
- [ ] Troubleshooting section: common issues (CORS, session, API rate limits)
- [ ] Architecture overview with link to technical-architecture.md

**Dependencies**: T028, T029  
**Deliverables**: `README.md`  
**Source Requirements**: R-012  

---

## Phase 6: Polish & Validation

**Description**: End-to-end testing, performance optimization, security hardening, error handling, and documentation.  
**Dependencies**: All previous phases  
**Estimated Duration**: 3 days  
**Delivers**: Production-ready, tested, documented application

---

### T032 — End-to-End Integration Testing
**Category**: Testing | **Priority**: High | **Effort**: 1 day

**Description**: Test the complete user journey: login → view dashboard → update time → verify burndown → view efficiency → trigger sync.

**Acceptance Criteria**:
- [ ] Login flow works with admin and member roles
- [ ] Sync pulls real GitHub data, updates DB, creates snapshot
- [ ] Time tracking update reflects in burndown chart within 30 seconds
- [ ] Efficiency chart shows correct estimated vs actual comparison
- [ ] Admin can trigger manual sync, create users
- [ ] Member cannot access admin functions (403)
- [ ] Logout destroys session, returns to login

**Dependencies**: All Phase 1–5 tasks  
**Deliverables**: Test results report, bug fixes  
**Source Requirements**: All requirements  

---

### T033 — Performance Optimization & Testing
**Category**: Testing | **Priority**: High | **Effort**: 0.5 days

**Description**: Measure and optimize API response times to meet <200ms KPI.

**Acceptance Criteria**:
- [ ] All dashboard API endpoints respond in <200ms (measured via browser DevTools Network tab)
- [ ] Burndown query uses precalculated `burndown_daily` — no heavy aggregation at request time
- [ ] Issues list supports pagination if >100 issues (LIMIT/OFFSET)
- [ ] MySQL queries use proper indexes (verified with EXPLAIN)
- [ ] Frontend bundle size < 500KB gzipped

**Dependencies**: T032  
**Deliverables**: Performance test results, any optimizations applied  
**Source Requirements**: KPI: API response time < 200ms  

---

### T034 — Security Review & Hardening
**Category**: Testing | **Priority**: High | **Effort**: 0.5 days

**Description**: Verify all security controls and harden the application against common vulnerabilities.

**Acceptance Criteria**:
- [ ] All SQL queries use parameterized prepared statements (no string concatenation)
- [ ] Passwords stored as bcrypt hash — verified in DB (no plaintext)
- [ ] Session cookies: `httpOnly=true`, `secure=true` (in production), `SameSite=Lax`
- [ ] Session ID regenerated on login (session fixation prevention)
- [ ] API input validation: numeric ranges for time fields, string length limits
- [ ] Error responses don't leak internal details (stack traces, SQL errors, file paths)
- [ ] `.env` file excluded from deployment and git
- [ ] GitHub PAT has minimum required scopes (`read:project`, `repo` read-only)

**Dependencies**: T032  
**Deliverables**: Security checklist completed, any vulnerabilities fixed  
**Source Requirements**: ADR-7, R-012  

---

### T035 — Error Handling & Resilience
**Category**: Development | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Add graceful error handling for all external dependencies and edge cases.

**Acceptance Criteria**:
- [ ] GitHub GraphQL errors: log failure, record in sync_history with status='failed', retry on next cron cycle
- [ ] Rate limit exceeded: log warning, skip sync, retry next cycle
- [ ] MySQL connection failure: return 503 with friendly error message
- [ ] Invalid time input: return 422 with validation error details
- [ ] Frontend: API errors display user-friendly toast/message (not raw error)
- [ ] Cron sync: catches all exceptions, logs to sync.log, exits cleanly

**Dependencies**: T032  
**Deliverables**: Error handling code throughout backend + frontend  
**Source Requirements**: R-012  

---

### T036 — Code Documentation & Comments
**Category**: Documentation | **Priority**: Medium | **Effort**: 0.5 days

**Description**: Add inline documentation to all PHP classes and Vue components. Ensure code is self-documenting and maintainable.

**Acceptance Criteria**:
- [ ] All PHP classes have PHPDoc class-level comments describing purpose
- [ ] All public methods have PHPDoc with `@param`, `@return`, `@throws`
- [ ] Complex logic sections have inline comments explaining the "why"
- [ ] Vue components have `<script>` comments describing props, emits, and usage
- [ ] `api.js` methods documented with JSDoc
- [ ] No dead code, no commented-out blocks, no TODO left unresolved

**Dependencies**: T032  
**Deliverables**: Documented codebase  
**Source Requirements**: R-012  

---

## Dependency Graph

### Critical Path
```
T001 → T002 → T003 → T005 → T006 → T010 → T012 → T018 → T022 → T032
```
Critical path length: **~16 days** (determines minimum project duration with infinite resources)

### Parallel Tracks

**Track A: GitHub Integration** (Phase 2)
```
T007 → T008 → T009 → T010 → T011 → T012
```

**Track B: Analytics Engine** (Phase 3, parallel with Track A)
```
T013 → T014
T015
T016
T017
```

**Track C: Frontend** (Phase 4, after Track A + B converge)
```
T018 → T019 → T020 → T021 → T022 → T023 → T024 → T025 → T026 → T027
```

**Track D: DevOps** (Phase 5, parallel with Track C)
```
T028 → T029 → T030 → T031
```

**Track E: Validation** (Phase 6, after all tracks)
```
T032 → T033 → T034 → T035 → T036
```

### Phase Dependency Diagram
```
Phase 0 (Done)
    │
    ▼
Phase 1 ─────────────────┐
    │                     │
    ├──► Phase 2          ├──► Phase 5
    │        │            │
    ├──► Phase 3          │
    │        │            │
    │    ◄───┘            │
    ▼                     │
Phase 4 ◄────────────────┘
    │
    ▼
Phase 6
```