# Project Plan — 01 - Scrum Master Assistant

**Project ID**: PRJ-01  
**Archetype**: standard  
**Start Date**: 2026-04-02  
**Target Completion**: 2026-04-30 (~20 working days with parallelization)  
**Last Updated**: 2026-10-24  
**Status**: ✅ All phases complete — Phases 0–6 (36 / 36 tasks) and Phase 7 post-release feedback (T037–T045); production verified and signed off by the owner 2026-10-24  

## Executive Summary

Build a GitHub-integrated Scrum project management dashboard deployed on cPanel shared hosting. The system syncs GitHub Projects (v2) data via GraphQL v4 API, adds custom time-tracking attributes (estimated/remaining/actual hours), and provides burndown charts, sprint health indicators, and per-member efficiency analysis. Tech stack: PHP 8.2 + Slim 4 backend, MySQL database, Vue 3 + Chart.js frontend, session-based authentication.

**Total Effort**: ~27 person-days across 36 tasks  
**Calendar Time**: ~20 working days (with parallel execution of independent phases)  
**Team Size**: Optimized for 1–2 developers  

**Completion**: 36 / 36 original development tasks complete (**100%**); 9 follow-up tasks (T037–T045) planned from live-site feedback  

## Project Phases

### Phase 0: Requirements Analysis & Architecture ✅ Complete
**Duration**: 1 day (2026-04-02)  
**Status**: ✅ Complete  
**Deliverables**:
- [x] Chinese → English requirements translation
- [x] Structured requirements (12 items: R-001 through R-012)
- [x] Business goals extraction with KPIs and success criteria
- [x] Domain concept analysis (8 entities, 5 business concepts)
- [x] Collaboration diagrams (8 Mermaid diagrams, 100% requirement coverage)
- [x] Technical architecture with 7 ADRs
- [x] Task breakdown (36 tasks across 6 phases)

### Phase 1: Foundation — Backend Skeleton + Auth + DB ✅ Complete
**Duration**: 5 days | **Completed**: 2026-04-03  
**Dependencies**: None  
**Tasks**: T001–T006  
**Deliverables**:
- [x] PHP 8.2 + Slim 4 project with Composer dependencies
- [x] MySQL schema (6 tables) with migration runner
- [x] Session-based authentication (login/logout/me endpoints)
- [x] Auth + Admin middleware for route protection
- [x] API route definitions (13 endpoints across 3 access levels)
- [x] Database seed script (initial admin user)

### Phase 2: GitHub GraphQL Integration ✅ Complete
**Duration**: 5 days | **Completed**: 2026-04-03  
**Dependencies**: Phase 1  
**Tasks**: T007–T012  
**Deliverables**:
- [x] GraphQL v4 query templates with cursor pagination
- [x] GitHubGraphQLService with rate limiting, retries, and error handling
- [x] Response parser (GraphQL JSON → local domain models)
- [x] Sync service with diff detection, snapshot generation, and history logging
- [x] Cron entry point with lock-file protection
- [x] Integration test against real GitHub project

### Phase 3: Analytics Engine ⟨parallel with Phase 2⟩ ✅ Complete
**Duration**: 4 days | **Completed**: 2026-04-04  
**Dependencies**: Phase 1  
**Tasks**: T013–T017  
**Deliverables**:
- [x] Burndown service (ideal vs actual curves per iteration)
- [x] Daily burndown snapshot job
- [x] Efficiency service (per-member estimated vs actual aggregation)
- [x] Time tracking service with audit trail (time_logs table)
- [x] Admin user management endpoints

### Phase 4: Frontend Dashboard ✅ Complete
**Duration**: 7 days | **Completed**: 2026-04-05  
**Dependencies**: Phase 2 + Phase 3  
**Tasks**: T018–T027  
**Deliverables**:
- [x] Vue 3 + Vite project with Pinia stores and Vue Router
- [x] Login view with session-based auth
- [x] Auth store + route guards (member and admin roles)
- [x] Dashboard view with burndown line chart (Chart.js)
- [x] Issues view with filterable/sortable table and inline time editing
- [x] Members view with grouped bar chart (efficiency analysis)
- [x] Sync status view with history and manual trigger (admin)
- [x] Admin view for user management
- [x] 30-second auto-refresh polling

### Phase 5: Deployment Pipeline ⟨parallel with Phase 4⟩ ✅ Complete
**Duration**: 3 days | **Completed**: 2026-04-05  
**Dependencies**: Phase 1  
**Tasks**: T028–T031  
**Deliverables**:
- [x] GitHub Actions workflow (build + SFTP deploy to cPanel)
- [x] cPanel cron job configuration (15-minute sync interval)
- [x] Environment configuration template (.env.example)
- [x] Deployment guide (README.md)

### Phase 6: Polish & Validation ✅ Complete
**Duration**: 3 days | **Started**: 2026-04-06  
**Dependencies**: All previous phases  
**Tasks**: T032–T036  
**Deliverables**:
- [x] **T032** — End-to-end integration testing — *completed 2026-10-24 (PHPUnit 121/121, smoke 20/20, manual browser checklist passed; 16 findings fixed — see `tests/e2e/FINDINGS.md`)*
- [x] **T033** — Performance optimization (API <200ms target) — *completed 2026-04-06*
- [x] **T034** — Security review & hardening (OWASP checklist — 17 items, all PASS) — *completed 2026-04-06*
- [x] **T035** — Error handling & resilience for all external dependencies — *completed 2026-04-06*
- [x] **T036** — Code documentation (PHPDoc + JSDoc) — *completed 2026-04-06*

> **T032 status (2026-10-24)**: complete — migrations, PHPUnit suite, smoke script and the manual browser checklist all pass.

### Phase 7: Post-Release Feedback ✅ Complete (owner sign-off 2026-10-24)
**Source**: Owner's live-site review of https://scrum.softercolor.com/ on 2026-10-24  
**Dependencies**: Phase 6  
**Tasks**: T037–T045 (~7.5 days) — all completed 2026-10-24; production verified by the owner  
**Change records**: [REQ-CHG-001](artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md) · [REQ-CHG-002](artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md) · [REQ-ADD-001](artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md)  

| # | Feedback | Change | Tasks | Priority |
|---|----------|--------|-------|----------|
| 3 | Estimate / remaining / actual hours are not synced; burndown is affected | REQ-CHG-001 | T037 field mapping → T038 persist + change detection + audit → T039 burndown accuracy → T040 tests/docs/backfill | High |
| 1 | Dashboard sprint dropdown only shows "Latest" — should list the project's iterations | REQ-CHG-002 | T041 iterations endpoint + current iteration → T042 selector wiring | High |
| 2 | Issues list needs pagination with a page-size dropdown | REQ-ADD-001 | T043 API pagination → T044 UI controls | Medium |
| — | Release gate | all | T045 verification and release | High |

**Suggested order**: T037 → T038 → T039 (hours/burndown first — the dashboard is not trustworthy without it); T041/T042 and T043/T044 can run in parallel with them; T040 and T045 close the phase.

**Owner decisions (2026-10-24)** — all three change records are now **Approved**:
1. GitHub fields: `Estimate Hours`, `Remains Hours`, `Actual Hours`; iteration field `Iteration`.
2. **GitHub wins; the dashboard is read-only for hours** (inline editor and `PUT /api/issues/{id}/time` are retired in T038).
3. Closed issues count as 0 remaining in the burndown.
4. Issues pagination is server-side, page sizes 10/25/50/100, default 25.

- [x] **T037** — Map GitHub number fields to hours (parser + config) — *2026-10-24*
- [x] **T038** — Persist synced hours (GitHub wins), read-only dashboard, audit — *2026-10-24*
- [x] **T039** — Burndown accuracy with synced hours (baseline, closed issues, rebuild script) — *2026-10-24*
- [x] **T040** — Hours sync tests, docs, production backfill — *2026-10-24 (production verified)*
- [x] **T041** — Iterations endpoint + current iteration in sync — *2026-10-24*
- [x] **T042** — Sprint selector wiring (Dashboard, Issues, Members) — *2026-10-24*
- [x] **T043** — Issues API server-side pagination, filters, sort, totals — *2026-10-24*
- [x] **T044** — Issues view pagination controls and page-size selector — *2026-10-24*
- [x] **T045** — Phase 7 verification and release — *2026-10-24 (PHPUnit 128/128, smoke 22/22, production verified, owner sign-off)*

## Timeline (Gantt Overview)

```
Week 1 (Days 1-5):  Phase 1 ████████████████████████████████████
Week 2 (Days 6-10): Phase 2 ████████████████████████████████████
                    Phase 3 ░░░░░░░░░░░░░░░░░░░░░░░░░░░░ (parallel)
Week 3 (Days 11-17):Phase 4 ██████████████████████████████████████████████████
                    Phase 5 ░░░░░░░░░░░░░░░░░ (parallel)
Week 4 (Days 18-20):Phase 6 ████████████████████
```

## Risk Management

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| GitHub API schema changes | Low | High | Pin GraphQL queries to known fields; add response validation |
| cPanel resource limits (memory/execution time) | Medium | Medium | Keep sync lightweight; use 15-min cron (not real-time) |
| SFTP deployment timeout | Low | Low | Use incremental sync (lftp mirror); exclude unchanged vendor/ |
| Session cross-origin issues in dev | Medium | Low | Configure Vite dev proxy for `/api/*` |
| MySQL connection limits on shared hosting | Low | Medium | Use persistent connections; close PDO after each request |
| GraphQL rate limit exceeded (5000 pts/hr) | Low | Medium | Track points per sync; skip if approaching limit |

## Resource Requirements

- **Backend Developer**: PHP 8.2, Slim 4, MySQL, GraphQL — primary workload Phases 1–3
- **Frontend Developer**: Vue 3, Chart.js, Pinia — primary workload Phase 4
- **DevOps**: GitHub Actions, cPanel, SFTP — Phase 5 (can be same person as backend)
- **Infrastructure**: cPanel shared hosting with MySQL 5.7+, PHP 8.2, cron access

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Sync data freshness | ≤ 15 minutes | Cron interval checks |
| API response time | < 200 ms | Browser DevTools Network tab |
| Burndown accuracy | Matches GitHub data 100% | Manual spot-check of counts |
| Sprint health visibility | Within 30 seconds of page load | Time to first meaningful paint |
| Estimation accuracy tracking | Per-member ratio calculated | Efficiency view shows ratio ±0.1 |

## References

- [Requirements Analysis](artifacts/Analysis/requirements.md)
- [Business Goals](artifacts/Analysis/goals.md)
- [Domain Concepts](artifacts/Analysis/domain-concepts.md)
- [Collaboration Diagrams](artifacts/Analysis/collaboration-diagrams.md)
- [Technical Architecture](artifacts/Analysis/technical-architecture.md)
- [Task Breakdown](artifacts/Analysis/task-breakdown.md)

## Related Changes

- [REQ-CHG-001](artifacts/Changes/2026-10-24-REQ-CHG-001-sync-hours-from-github.md) - Sync time-tracking hours from GitHub Project fields; GitHub wins, dashboard read-only (High, Approved)
- [REQ-CHG-002](artifacts/Changes/2026-10-24-REQ-CHG-002-dashboard-sprint-selector-iterations.md) - Dashboard sprint selector lists project iterations (High, Approved)
- [REQ-ADD-001](artifacts/Changes/2026-10-24-REQ-ADD-001-issues-pagination.md) - Issues pagination with page-size selector (Medium, Approved)