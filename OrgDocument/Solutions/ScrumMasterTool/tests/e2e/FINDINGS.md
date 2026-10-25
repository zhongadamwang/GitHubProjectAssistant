# E2E Findings — ScrumMasterTool (T032)

**Run Date**: 2026-10-24  
**Tester**: Copilot (automated + manual browser run)  
**Baseline**: Phase 1–5 complete (T001–T031)  

## Findings

| # | Severity | Endpoint / Area | Description | Status | Fixed In |
|---|----------|----------------|-------------|--------|----------|
| 1 | Blocker | `tests/bootstrap.php` | Passed `DB_DATABASE/DB_USERNAME/DB_PASSWORD` to `migrate.php`, which reads `DB_NAME/DB_USER/DB_PASS` — migrations targeted the wrong DB/credentials | Fixed | tests/bootstrap.php |
| 2 | Blocker | `database/migrate.php` | `.env` overrode explicitly supplied `DB_*` environment variables | Fixed | database/migrate.php |
| 3 | Major | `database/seed.php` | Read `DB_DATABASE/DB_USERNAME/DB_PASSWORD`, so it could never run with the documented `.env` (`DB_NAME/DB_USER/DB_PASS`) | Fixed | database/seed.php |
| 4 | Major | `tests/bootstrap.php` | Bootstrap output (PHP 8.4+ `session.use_only_cookies` deprecation, `[OK]` lines) marked headers as sent, so sessions never worked in tests (all authenticated calls returned 401) | Fixed | tests/bootstrap.php |
| 5 | Minor | `SyncIntegrationTest` | Stale `SyncService` constructor (missing `burndownService`) | Fixed | SyncIntegrationTest.php |
| 6 | Minor | `github-project-response.json` | Fixture used `data.user.projectV2`; sync reads `data.organization.projectV2` | Fixed | fixture |
| 7 | Minor | `IntegrationTestCase` | `DELETE FROM users` violated `time_logs.changed_by` FK (RESTRICT) | Fixed | IntegrationTestCase.php |
| 8 | Minor | `MiddlewareTest`, `EndToEndTest`, `api-smoke.sh` | Stale expectations: 501 placeholders, burndown 200 for unknown project (now 404), admin create-user payloads missing required `role` | Fixed | tests + smoke script |
| 9 | Minor | `.env.test` / `.env.test.example` | Used pre-rename `GITHUB_*` keys; `REPO_PAT` must be non-empty for the container to build `SyncController` | Fixed | .env.test.example |
| 10 | Blocker | `main.js` | Router installed before `fetchMe()` resolved, so every browser refresh redirected to `/login` | Fixed | frontend/src/main.js |
| 11 | Blocker | `BurndownChart.vue`, `EfficiencyChart.vue` | Chart watcher ran before the `v-if` canvas was rendered, so charts loaded asynchronously never drew (blank) | Fixed | `flush: 'post'` on watchers |
| 12 | Blocker | Issues view / `IssueTimeEditor` / `projectStore` | UI used `estimated_hours`, `state: 'CLOSED'`, `number`; API sends `estimated_time`, `status: 'closed'`, `id` (and DECIMALs as strings) — all issues showed Open with 0 hours and edits sent the wrong payload | Fixed | frontend stores/views |
| 13 | Major | Members view | UI used `login` / `issue_count`; API sends `member` / `issues_count` | Fixed | MembersView, EfficiencyChart |
| 14 | Major | Sync view + `GET /api/sync/history` | View read `data.history` (API sends `data`); endpoint returned nothing without `project_id` and never showed failed runs (`project_id` NULL) | Fixed | SyncView, SyncHistoryRepository, SyncController |
| 15 | Major | `App.vue` | No navigation or logout button — only URL typing reached Issues/Members/Sync/Admin | Fixed | new NavBar.vue |
| 16 | Minor | Admin view | "Last Login" column read a non-existent `last_login_at` (always "Never") | Fixed | now shows `created_at` |

## Severity Definitions

| Severity | Definition |
|----------|------------|
| **Blocker** | Prevents T032 from being marked complete; must be fixed before Phase 6 can advance |
| **Major** | Significant functional defect; should be fixed in T035 (Error Handling) or T033/T034 |
| **Minor** | Cosmetic or edge-case issue; tracked for awareness but not blocking |

## Testing Checklist

- [x] `tests/e2e/api-smoke.sh` executed against running instance — exits 0 (20/20)
- [x] `vendor/bin/phpunit` executed — exits 0 (121 tests, 0 failures)
- [x] Vue frontend manually exercised (2026-10-24, integrated browser, PHP dev server + temp MySQL):
  - [x] Login page loads and accepts credentials
  - [x] Dashboard renders burndown chart
  - [x] Issues view: inline time edit works (PUT /api/issues/{id}/time; persisted + time_logs rows)
  - [x] Members view: efficiency bar chart renders
  - [x] Sync status view: history table populates (Sync Now → 502 surfaced as error with dummy PAT)
  - [x] Admin view: user list loads; create user form works (validation, create, duplicate 409)
- [x] 30-second auto-refresh confirmed (burndown requests at t=0s, 30s, 60s)
- [x] Session expiry test: cleared session → API returns 401 → frontend redirects to /login
- [x] Member role: `/admin` redirects to dashboard; Admin link hidden; Log out returns to /login
- [x] Member → POST /api/sync/trigger → returns 403 (EndToEndTest)
- [x] ~~Time update survives sync~~ — superseded: hours now come from GitHub and are read-only (see Phase 7 below)

## Phase 7 verification — live-site feedback (2026-10-24)

Local run (PHP dev server + temp MySQL, 60 seeded issues, 4 sprints). Change records: REQ-CHG-001, REQ-CHG-002, REQ-ADD-001.

- [x] `vendor/bin/phpunit` — 128 tests, 0 failures (new: hours mapping, GitHub-wins overwrite + audit, field-only change detection, custom field names, iteration config + current sprint, closed-issue aggregation, burndown baseline, issues pagination/validation, iterations endpoint)
- [x] `tests/e2e/api-smoke.sh` — 22/22 (adds iterations, paged/invalid issues, retired `PUT /issues/{id}/time` → 404)
- [x] Dashboard: sprint dropdown lists Sprint 4 / 3 (current) / 2 / 1, current preselected; switching loads that sprint; sprint without data shows the empty state
- [x] Issues: 25 rows by default; page-size dropdown 10/25/50/100; navigation, "Showing x–y of N", ellipsis; filter and sort apply to the whole result and return to page 1; page size remembered after reload; hours are plain text (no inputs); totals cover all matching issues
- [x] `cron/rebuild-burndown.php` removes zero-estimate snapshots and captures today's snapshot
- [ ] **Production**: deploy, run a real "Sync Now" with the real GitHub project, run `php cron/rebuild-burndown.php`, confirm hours / sprints / burndown match GitHub (needs owner)
- [ ] Verify on the real project that `Estimate Hours`, `Remains Hours`, `Actual Hours` are NUMBER fields and `Iteration` is an ITERATION field
