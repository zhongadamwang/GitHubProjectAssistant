# Task Tracking — Scrum Master Assistant

**Project**: PRJ-01 — Scrum Master Assistant  
**Last Updated**: 2026-04-04  
**Status**: Planning Complete — Ready for Phase 1 Development  

## Current Sprint: Phase 1 — Foundation

### Active Tasks
| Task ID | Title | Assignee | Status | Priority | Effort | Task File |
|---------|-------|----------|--------|----------|--------|-----------|
| T002 | Create MySQL Schema & Migrations | TBD | Completed | High | 1d | [task-dev-mysql-schema-migrations.md](task-dev-mysql-schema-migrations.md) |





### Backlog — Phase 2: GitHub GraphQL Integration
| Task ID | Title | Effort | Priority | Dependencies | Task File |
|---------|-------|--------|----------|--------------|-----------|
| ~~T007~~ | ~~Write GraphQL Query Templates~~ | ~~0.5d~~ | ~~High~~ | ~~T001~~ | ~~[task-dev-graphql-query-templates.md](task-dev-graphql-query-templates.md)~~ |
| ~~T008~~ | ~~Implement GitHubGraphQLService~~ | ~~1.5d~~ | ~~High~~ | ~~T007~~ | ~~[task-dev-github-graphql-service.md](task-dev-github-graphql-service.md)~~ |
| ~~T009~~ | ~~Implement GraphQL ResponseParser~~ | ~~0.5d~~ | ~~High~~ | ~~T007~~ | ~~[task-dev-graphql-response-parser.md](task-dev-graphql-response-parser.md)~~ |
| ~~T010~~ | ~~Build Sync Logic with Diff & Snapshot~~ | ~~1.5d~~ | ~~High~~ | ~~T008, T009, T002~~ | ~~[task-dev-sync-service.md](task-dev-sync-service.md)~~ |
| ~~T011~~ | ~~Create Cron Sync Entry Point~~ | ~~0.5d~~ | ~~High~~ | ~~T010~~ | ~~[task-dev-cron-sync-entry.md](task-dev-cron-sync-entry.md)~~ |
| ~~T012~~ | ~~Integration Test: GitHub Sync E2E~~ | ~~0.5d~~ | ~~High~~ | ~~T010, T011~~ | ~~[task-dev-sync-integration-test.md](task-dev-sync-integration-test.md)~~ |

### Backlog — Phase 3: Analytics Engine
| Task ID | Title | Effort | Priority | Dependencies | Task File |
|---------|-------|--------|----------|--------------|-----------|

| ~~T014~~ | ~~Build Daily Burndown Snapshot Job~~ | ~~0.5d~~ | ~~High~~ | ~~T002, T013~~ | ~~[task-dev-burndown-snapshot-job.md](task-dev-burndown-snapshot-job.md)~~ |
| ~~T015~~ | ~~Implement EfficiencyService~~ | ~~1d~~ | ~~High~~ | ~~T002, T004, T006~~ | ~~[task-dev-efficiency-service.md](task-dev-efficiency-service.md)~~ |
| ~~T016~~ | ~~Implement TimeTrackingService with Audit~~ | ~~1d~~ | ~~High~~ | ~~T002, T005, T006~~ | ~~[task-dev-time-tracking-service.md](task-dev-time-tracking-service.md)~~ |
| ~~T017~~ | ~~Implement Admin User Management Endpoints~~ | ~~0.5d~~ | ~~Medium~~ | ~~T005, T006~~ | ~~[task-dev-admin-user-management.md](task-dev-admin-user-management.md)~~ |

### Backlog — Phase 4: Frontend Dashboard
| Task ID | Title | Effort | Priority | Dependencies | Task File |
|---------|-------|--------|----------|--------------|-----------|
| T018 | Initialize Vue 3 + Vite Project | 0.5d | High | Phase 2+3 | [task-dev-vue-initialize.md](task-dev-vue-initialize.md) |
| T019 | Build Login View | 0.5d | High | T018 | [task-dev-login-view.md](task-dev-login-view.md) |
| T020 | Build Auth Store & Route Guards | 0.5d | High | T019 | [task-dev-auth-store.md](task-dev-auth-store.md) |
| T021 | Build API Service Layer | 0.5d | High | T018 | [task-dev-api-service-layer.md](task-dev-api-service-layer.md) |
| T022 | Build Dashboard View with Burndown Chart | 1.5d | High | T021 | [task-dev-dashboard-view.md](task-dev-dashboard-view.md) |
| T023 | Build Issues View with Time Editor | 1.5d | High | T021 | [task-dev-issues-view.md](task-dev-issues-view.md) |
| T024 | Build Members View with Efficiency Charts | 1d | High | T021 | [task-dev-members-view.md](task-dev-members-view.md) |
| T025 | Build Sync Status View | 0.5d | Medium | T021, T020 | [task-dev-sync-status-view.md](task-dev-sync-status-view.md) |
| T026 | Build Admin View (User Management) | 0.5d | Medium | T020, T021 | [task-dev-admin-view.md](task-dev-admin-view.md) |
| T027 | Implement Auto-Refresh & Polling | 0.5d | Medium | T022, T023 | [task-dev-auto-refresh.md](task-dev-auto-refresh.md) |

### Backlog — Phase 5: Deployment Pipeline
| Task ID | Title | Effort | Priority | Dependencies | Task File |
|---------|-------|--------|----------|--------------|-----------|
| ~~T028~~ | ~~Create GitHub Actions Deploy Workflow~~ | ~~1d~~ | ~~High~~ | ~~T001, T004, T018~~ | ~~[task-dev-github-actions-deploy.md](task-dev-github-actions-deploy.md)~~ |
| T029 | Configure cPanel Cron Job | 0.5d | High | T011, T028 | [task-dev-cpanel-cron.md](task-dev-cpanel-cron.md) |
| ~~T030~~ | ~~Write Environment Configuration Template~~ | ~~0.5d~~ | ~~Medium~~ | ~~T001~~ | ~~[task-dev-env-config-template.md](task-dev-env-config-template.md)~~ |
| T031 | Write Deployment Guide | 1d | Medium | T028, T029 | [task-dev-deployment-guide.md](task-dev-deployment-guide.md) |

### Backlog — Phase 6: Polish & Validation
| Task ID | Title | Effort | Priority | Dependencies |
|---------|-------|--------|----------|--------------|
| T032 | End-to-End Integration Testing | 1d | High | All Phase 1–5 |
| T033 | Performance Optimization & Testing | 0.5d | High | T032 |
| T034 | Security Review & Hardening | 0.5d | High | T032 |
| T035 | Error Handling & Resilience | 0.5d | Medium | T032 |
| T036 | Code Documentation & Comments | 0.5d | Medium | T032 |

### Completed Tasks
| Task | Completed Date | Notes |
|------|---------------|-------|
| T030 — Write Environment Configuration Template | 2026-04-05 | Audited all `$_ENV` usage; added `GITHUB_PROJECT_NUMBER` (was missing), `APP_URL`, seed vars (`ADMIN_EMAIL`/`ADMIN_PASSWORD`/`ADMIN_NAME`); added DO-NOT-COMMIT header; production callouts for `APP_ENV` and `SESSION_SECURE`; corrected var name `ADMIN_NAME` (not ADMIN_DISPLAY_NAME) |
| T028 — Create GitHub Actions Deploy Workflow | 2026-04-05 | `.github/workflows/deploy.yml` at repo root; checkout → Node 20 + npm ci + build → PHP 8.2 + composer install --no-dev → lftp SFTP mirror (excludes .env, tests/, frontend/, snapshots/) → appleboy/ssh-action migrate; `workflow_dispatch` for manual runs; 9 secrets: SFTP_HOST/PORT/USER/PASSWORD, REMOTE_PATH, SSH_HOST/USER/KEY/PORT |
| T027 — Implement Auto-Refresh & Polling | 2026-04-05 | `dashboardStore.startPolling(30s)` + `stopPolling()` with in-flight guard; `projectStore.startPolling(60s)` + `stopPolling()`; `DashboardView` mounts/unmounts polling; `IssuesView` mounts/unmounts polling + immediate re-fetch on `IssueTimeEditor` `saved` event |
| T026 — Build Admin View (User Management) | 2026-04-05 | `AdminView.vue` — users table + Add User form; client-side validation (email, min-8 password); 409/422 error handling; new user appended without reload; route guarded by `requiresAdmin` |
| T025 — Build Sync Status View | 2026-04-05 | `SyncStatus.vue` (ok/stale/error/unknown indicator); `SyncView.vue` — history table (top 20), summary stats, "Sync Now" button (admin-only, loading spinner, 4s auto-dismiss feedback) |
| T024 — Build Members View with Efficiency Charts | 2026-04-05 | `EfficiencyChart.vue` (grouped bar: blue=estimated, orange=actual); `MembersView.vue` — iteration filter, chart, accuracy table with ratio color coding (accurate 0.9–1.1/over/under); N/A guard for zero estimates |
| T023 — Build Issues View with Time Editor | 2026-04-05 | `projectStore.js` (filteredIssues computed, filter/sort state, totals getter, saveIssueTime optimistic update); `IssueTimeEditor.vue` (3 numeric inputs, blur/Enter save, flash-success/error, emits `saved`); `IssuesView.vue` (filter controls, sortable headers, footer totals, 60s polling) |
| T022 — Build Dashboard View with Burndown Chart | 2026-04-05 | `dashboardStore.js` (fetchBurndown, refresh, health getter, startPolling/stopPolling); `BurndownChart.vue` (ideal dashed blue, actual solid red, Chart.js destroy guard); `SprintSelector.vue`; `HealthBadge.vue`; `DashboardView.vue` |
| T021 — Build API Service Layer | 2026-04-05 | `frontend/src/services/api.js` — Axios instance (baseURL=/api, withCredentials); 401 interceptor (dynamic import to avoid circular deps); 13 named exports covering all API endpoints |
| T020 — Build Auth Store & Route Guards | 2026-04-05 | `authStore.js` (Pinia Options API: user state, isAuthenticated/isAdmin getters, login/logout/fetchMe/clearAuth actions); `router.beforeEach()` guards `requiresAuth` + `requiresAdmin` meta |
| T019 — Build Login View | 2026-04-05 | `LoginView.vue` — email/password form, `@submit.prevent`, 401→"Invalid credentials" / generic fallback, button disabled during request, Enter submits, scoped card layout |
| T018 — Initialize Vue 3 + Vite Project | 2026-04-05 | `frontend/` scaffold: `package.json`, `vite.config.js` (build.outDir=../public/dist, proxy /api→localhost:8080), `index.html`, `App.vue`, `main.js`; all dirs created; `.gitignore` updated; `public/index.php` SPA fallback added | `UserRepository::findAll()` (no password_hash); `AdminController::listUsers()` (200 + users array); `AdminController::createUser()` (201/409/422, validates email/display_name/password/role); `container.php` AdminController injects UserRepository; 7-test `AdminControllerTest` in Phase3/ |
| T016 — Implement TimeTrackingService with Audit | 2026-04-04 | `TimeLogRepository` (insert, findByIssue); `TimeTrackingService` (validated partial update, PDO transaction, read-before-write audit); `IssueRepository::findByProject()` (filter builder) + `getCountsByProject()` + `findById()`; `ProjectRepository::findAll()` + `findById()`; `IssueController` (getIssues, updateTime — auth_user from request); `ProjectController` (listProjects, getProject with live counts); `TimeLogRepository`+`TimeTrackingService` wired; `IssueController`/`ProjectController` replaced with injected versions; routes updated; 7-test `TimeTrackingServiceTest` in Phase3/ |
| T015 — Implement EfficiencyService | 2026-04-04 | `IssueRepository::aggregateEfficiencyByMember()` + `aggregateEfficiencyByMemberAndIteration()`; `EfficiencyService` (getMemberEfficiency, getMemberTrend, null-safe ratio); `MemberController` (GET /api/projects/{id}/members); container+routes wired; 9-test `EfficiencyServiceTest` in Phase3/ |
| T014 — Build Daily Burndown Snapshot Job | 2026-04-04 | `IssueRepository::aggregateTimeByIteration()` (GROUP BY iteration, COALESCE for NULLs); `BurndownService::captureDaily()` full impl (replaces stub); `BurndownService` constructor now injects `IssueRepository`; `SyncService` step 6b hook with try/catch guard; `container.php` updated for both services; 5-test `CaptureDailyTest` suite (value mapping, idempotency, multi-iteration, empty project, UTC date) |
| T013 — Implement BurndownService | 2026-04-04 | `BurndownPoint` VO; `BurndownRepository` (getPointsForIteration, getLatestIteration, upsertDailySnapshot); `BurndownService` (getBurndown linear ideal + carry-forward actual, captureDaily stub); `BurndownController` (GET /api/projects/{id}/burndown); container+routes wired; 5-test PHPUnit mock suite in Phase3/ |
| T012 — Integration Test: GitHub Sync E2E | 2026-04-03 | 6 tests (first-run, idempotency, history rows, update detection, snapshot, API-failure, time-field preservation); `GitHubClientInterface` extracted; timestamp normalization fixed; migration 007 |
| T011 — Create Cron Sync Entry Point | 2026-04-03 | `cron/sync.php` (PID lock, shutdown cleanup, exit codes 0/1/2); `SyncController` real impl (history + trigger); container updated |
| T010 — Build Sync Logic with Diff & Snapshot | 2026-04-03 | `SyncService`, `SyncResult`, `ProjectRepository`, `IssueRepository`, `SyncHistoryRepository` — full 7-step sync cycle; time fields never overwritten |
| T009 — Implement GraphQL ResponseParser | 2026-04-03 | `src/GraphQL/ResponseParser.php`, `src/Models/Project.php`, `src/Models/Issue.php` — static parser with `__typename` dispatch + key-presence fallback; `E_USER_WARNING` for unknown types |
| T008 — Implement GitHubGraphQLService | 2026-04-03 | `src/Services/GitHubGraphQLService.php` (cURL, retry, pagination, rate-limit), `src/Exceptions/GitHubApiException.php`, `src/Exceptions/RateLimitException.php`; wired into container |
| T007 — Write GraphQL Query Templates | 2026-04-03 | `src/GraphQL/Queries.php` — `Queries` final class with `FETCH_VIEWER`, `FETCH_PROJECT_FIELDS`, `FETCH_PROJECT_ITEMS` heredoc constants; `get()` + `variables()` static helpers; full inline-fragment coverage for all field value types |
| T001 — Initialize PHP Backend Project | 2026-04-03 | `OrgDocument/Solutions/ScrumMasterTool/` scaffold: `composer.json`, `.env.example`, `.gitignore`, full `src/` skeleton; `composer install` pending |
| T002 — Create MySQL Schema & Migrations | 2026-04-03 | 6 migration SQL files + `migrate.php` idempotent runner; `migrations_log` auto-created; pending live MySQL test run |
| T003 — Create Database Seed Script | 2026-04-03 | `database/seed.php` — idempotent admin seeder; bcrypt cost 12; `.env` + CLI arg override; skip-on-duplicate logic |
| T004 — Set Up Slim 4 Entry Point & Middleware | 2026-04-03 | `public/index.php`, `config/settings.php`, `config/container.php`, `CorsMiddleware`, `JsonResponseMiddleware`, `public/.htaccess`; health route `GET /api/health` |
| T005 — Implement Authentication System | 2026-04-03 | `User` model, `UserRepository`, `AuthService` (session fixation guard, timing-safe), `AuthController` (login/logout/me), `AuthMiddleware` (401), `AdminMiddleware` (403); container wired |
| T006 — Configure Route Definitions | 2026-04-03 | `config/routes.php` — 13 endpoints across 3 groups (public/auth/admin); 4 placeholder controllers (501); all wired into container |
| Project Structure Setup | 2026-04-02 | EDPS project structure initialized |
| Requirements Translation | 2026-04-02 | Chinese → English translation |
| Requirements Analysis | 2026-04-02 | 12 structured requirements (R-001–R-012) |
| Goals Extraction | 2026-04-02 | Business goals, KPIs, success criteria |
| Domain Concept Analysis | 2026-04-02 | 8 entities, 5 business concepts |
| Collaboration Diagrams | 2026-04-02 | 8 diagrams, 100% requirement coverage |
| Technical Architecture | 2026-04-02 | 7 ADRs, full system design |
| Task Breakdown | 2026-04-02 | 36 tasks across 6 phases |
| Project Plan Update | 2026-04-02 | Phases, timeline, risks, metrics defined |

### Blocked Tasks
| Task | Blocker | Since | Owner |
|------|---------|-------|-------|
| *No blocked tasks* | - | - | - |

## Progress Summary
- **Total Tasks**: 36 development tasks (0 active sprint, 8 backlog) + 9 planning tasks completed
- **Phases Complete**: Phase 1 ✅, Phase 2 ✅, Phase 3 ✅, Phase 4 ✅
- **Phase 5 Progress**: T028 ✅, T030 ✅ — T029 and T031 remaining
- **Phases Remaining**: Phase 5 (2 tasks left: T029, T031), Phase 6 (Polish & Validation — 5 tasks)
- **Critical Path**: T029 (cron config) → T031 (deployment guide) → T032 (E2E testing)

## Next Actions
1. T029 — Configure cPanel Cron Job (requires live cPanel access + deployed files from T028)
2. T031 — Write Deployment Guide (can draft now that T028 and T030 are done)
3. Phase 6: T032–T036 after T031 completes