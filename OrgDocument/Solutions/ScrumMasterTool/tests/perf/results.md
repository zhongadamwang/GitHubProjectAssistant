# Performance Benchmark Results — ScrumMasterTool

**Project**: PRJ-01 — Scrum Master Assistant  
**Target**: All 13 API endpoints respond in `< 200 ms` TTFB under normal load.  
**Methodology**: `curl -o /dev/null -s -w "%{time_starttransfer}"` against a warmed connection; 10 runs per endpoint; min/avg/max reported.  
**Run script**: `tests/perf/benchmark.sh`  

## Audit Summary (Code Review)

The following items were audited against the codebase before running live benchmarks:

| Check | Status | Notes |
|-------|--------|-------|
| `BurndownRepository::getPointsForIteration()` — single SQL fetch | ✅ Pass | Single `SELECT … WHERE project_id = ? AND iteration = ?` — no per-date loop |
| `EfficiencyService::getMemberEfficiency()` — SQL-level aggregation | ✅ Pass | Delegates to `IssueRepository::aggregateEfficiencyByMember()` which uses `GROUP BY assignee` in SQL |
| `IssueRepository::findByProject()` — prepared statements only | ✅ Pass | All filter conditions use bound params (`$params['key']`); no string interpolation in query body |
| Index `issues(project_id)` | ✅ Exists | `idx_issues_project_id` declared in migration 003 |
| Index `issues(project_id, iteration)` | ✅ Exists | `idx_issues_project_iteration` declared in migration 003 |
| Index `burndown_daily(project_id, iteration, snapshot_date)` | ✅ Exists | `UNIQUE KEY uq_burndown_project_iteration_date` declared in migration 006 — doubles as a covering index |
| Index `time_logs(issue_id)` | ✅ Exists | `idx_time_logs_issue_id` declared in migration 004 |
| `BurndownChart.vue` — `chart.destroy()` guard | ✅ Pass | Guard in both `buildChart()` and `onUnmounted()` |
| `EfficiencyChart.vue` — `chart.destroy()` guard | ✅ Pass | Guard in both `buildChart()` and `onUnmounted()` |
| `Cache-Control: no-store` on `/api/*` | ✅ Added | Set in `JsonResponseMiddleware` (T033) |

> **Migration 008** (`008_add_performance_indexes.sql`) is a defensive no-op migration.
> All indexes listed above already existed from migrations 003, 004, and 006.
> The migration uses `CREATE INDEX IF NOT EXISTS` / `INFORMATION_SCHEMA` guards so it is
> safe to run on any database state, and idempotent with the migration runner.

---

<!-- Benchmark run results are appended below by benchmark.sh -->

---

## Phase 8 — multi-project sizing check (2026-10-25)

**Environment**: local PHP 8.5 built-in server + MySQL 8, 10 tracked projects × 1,000 issues (10,000 issues), 5 timed requests after a warm-up. Documented system limitation: up to 10 projects of about 1,000 issues each.

| Endpoint | Avg (ms) | Max (ms) | ≤ 200 ms? |
|----------|----------|----------|-----------|
| `GET /api/projects` | 1.8 | 2.2 | ✅ |
| `GET /api/projects/{id}/issues?page=1&page_size=100` | 3.8 | 4.0 | ✅ |
| `GET /api/projects/{id}/issues?page=5&page_size=25&sort=estimated_time&dir=desc` | 3.7 | 3.8 | ✅ |
| `GET /api/projects/{id}/issues?status=open&assignee=alice&page_size=50` | 3.7 | 3.8 | ✅ |
| `GET /api/projects/{id}/iterations` | 2.4 | 2.4 | ✅ |
| `GET /api/projects/{id}/members` | 6.4 | 6.6 | ✅ |
| `GET /api/projects/{id}/burndown?iteration=Sprint 1` | 1.7 | 1.9 | ✅ |
| `GET /api/sync/history` and `?project_id={id}` | 1.6–1.7 | 2.0–2.2 | ✅ |
| `GET /api/admin/github-accounts` | 1.7 | 2.0 | ✅ |

Not measured: sync duration (dominated by GitHub's GraphQL latency, ~10 pages per 1,000-issue project) and the production host's request time limit. "Sync Now" is per project for that reason; the cron job syncs all projects sequentially.
