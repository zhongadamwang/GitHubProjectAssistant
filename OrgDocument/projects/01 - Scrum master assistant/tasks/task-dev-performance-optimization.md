# T033 — Performance Optimization & Testing

**Task ID**: T033  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-06  
**Assignee**: TBD  
**Sprint**: Phase 6 — Polish & Validation  

### Description
Profile all REST API endpoints and Vue front-end load paths; identify and fix bottlenecks so that every API response meets the `< 200 ms` target under normal load. Covers backend query optimization, HTTP caching headers, frontend bundle size, and Chart.js render performance.

### Acceptance Criteria
- [ ] All 13 API endpoints respond in `< 200 ms` (measured as TTFB with `curl -o /dev/null -s -w "%{time_starttransfer}"` against a warmed connection; results documented in `tests/perf/results.md`)
- [ ] `GET /api/projects/{id}/burndown` — N+1 query eliminated; single SQL fetch returns all burndown points for the requested iteration
- [ ] `GET /api/projects/{id}/issues` — uses indexed `project_id` + `iteration` columns; `EXPLAIN` shows no full-table scan
- [ ] `GET /api/projects/{id}/members` — efficiency aggregation query uses a single `GROUP BY` SQL (not PHP-level aggregation loop)
- [ ] Frontend initial bundle (`public/dist/`) ≤ 500 KB gzipped total (verified with `vite build --report`)
- [ ] Chart.js instances are destroyed before re-render (`chart.destroy()` guard confirmed in `BurndownChart.vue` and `EfficiencyChart.vue`)
- [ ] No duplicate API calls on initial page load (verified via Network tab — each view fetches once on mount)
- [ ] HTTP response headers include `Cache-Control: no-store` on all `/api/*` routes to prevent stale auth state from being cached by proxies

### Tasks/Subtasks
- [ ] Write `tests/perf/benchmark.sh` — loops over all 13 endpoints 10× each with `curl`; outputs min/avg/max TTFB; appends results to `tests/perf/results.md`
- [ ] Run benchmark; identify any endpoints ≥ 200 ms
- [ ] Add database indexes if missing: `issues(project_id)`, `issues(project_id, iteration)`, `burndown_snapshots(project_id, iteration, snapshot_date)`, `time_logs(issue_id)` — add via a new migration `008_add_performance_indexes.sql`
- [ ] Audit `GET /api/projects/{id}/burndown`: confirm `BurndownRepository::getPointsForIteration()` fetches rows in one query; refactor if looping per-date
- [ ] Audit `GET /api/projects/{id}/members`: confirm `EfficiencyService` uses SQL aggregation (`aggregateEfficiencyByMember`), not PHP `foreach` totalling
- [ ] Audit `GET /api/projects/{id}/issues`: add `iteration` column index; confirm `IssueRepository::findByProject()` filter uses prepared statement with bound params (no concatenated SQL)
- [ ] Run `npm run build` with `--report` flag; review chunk sizes; if any chunk > 200 KB, evaluate dynamic import or tree-shaking
- [ ] Add `Cache-Control: no-store` header to `JsonResponseMiddleware` (or per-group in `routes.php`) for all `/api/*` responses
- [ ] Re-run benchmark after fixes; confirm all endpoints < 200 ms; update `tests/perf/results.md` with before/after comparison
- [ ] Verify Chart.js destroy guard in `BurndownChart.vue` and `EfficiencyChart.vue` with Vue DevTools (no memory leak on repeated navigation)

### Definition of Done
- [ ] All acceptance criteria met  
- [ ] `tests/perf/results.md` present with before/after TTFB numbers  
- [ ] Migration `008_add_performance_indexes.sql` created (if indexes were missing)  
- [ ] `composer test` still passes after any backend changes  

### Dependencies
- T032 — End-to-End Integration Testing (baseline must be stable)  

### Effort Estimate
**Time Estimate**: 0.5 day  

### Priority
High — API `< 200 ms` is a stated success metric (see project-plan.md)  

### Labels/Tags
- Category: performance  
- Component: backend, frontend, database  
- Sprint: Phase 6 — Polish & Validation  

### Notes
- Benchmark with `APP_ENV=production` and `opcache.enable=1` to reflect real cPanel conditions  
- Do not add in-process caching (e.g., APCu) — shared hosting may not have APCu; SQL-level optimization is sufficient for 6–15 users  
- Source Requirements: R-011, R-012 (hosting constraints), R-005 (sprint health visibility within 30 seconds)  

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-06
