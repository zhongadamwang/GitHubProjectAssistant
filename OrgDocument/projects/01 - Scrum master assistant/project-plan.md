# Project Plan — 01 - Scrum Master Assistant

**Project ID**: PRJ-01  
**Archetype**: standard  
**Start Date**: 2026-04-02  
**Target Completion**: 2026-04-30 (~20 working days with parallelization)  
**Status**: Planning Complete — Ready for Development  

## Executive Summary

Build a GitHub-integrated Scrum project management dashboard deployed on cPanel shared hosting. The system syncs GitHub Projects (v2) data via GraphQL v4 API, adds custom time-tracking attributes (estimated/remaining/actual hours), and provides burndown charts, sprint health indicators, and per-member efficiency analysis. Tech stack: PHP 8.2 + Slim 4 backend, MySQL database, Vue 3 + Chart.js frontend, session-based authentication.

**Total Effort**: ~27 person-days across 36 tasks  
**Calendar Time**: ~20 working days (with parallel execution of independent phases)  
**Team Size**: Optimized for 1–2 developers  

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

### Phase 1: Foundation — Backend Skeleton + Auth + DB
**Duration**: 5 days  
**Dependencies**: None  
**Tasks**: T001–T006  
**Deliverables**:
- PHP 8.2 + Slim 4 project with Composer dependencies
- MySQL schema (6 tables) with migration runner
- Session-based authentication (login/logout/me endpoints)
- Auth + Admin middleware for route protection
- API route definitions (13 endpoints across 3 access levels)
- Database seed script (initial admin user)

### Phase 2: GitHub GraphQL Integration
**Duration**: 5 days  
**Dependencies**: Phase 1  
**Tasks**: T007–T012  
**Deliverables**:
- GraphQL v4 query templates with cursor pagination
- GitHubGraphQLService with rate limiting, retries, and error handling
- Response parser (GraphQL JSON → local domain models)
- Sync service with diff detection, snapshot generation, and history logging
- Cron entry point with lock-file protection
- Integration test against real GitHub project

### Phase 3: Analytics Engine ⟨parallel with Phase 2⟩
**Duration**: 4 days  
**Dependencies**: Phase 1  
**Tasks**: T013–T017  
**Deliverables**:
- Burndown service (ideal vs actual curves per iteration)
- Daily burndown snapshot job
- Efficiency service (per-member estimated vs actual aggregation)
- Time tracking service with audit trail (time_logs table)
- Admin user management endpoints

### Phase 4: Frontend Dashboard
**Duration**: 7 days  
**Dependencies**: Phase 2 + Phase 3  
**Tasks**: T018–T027  
**Deliverables**:
- Vue 3 + Vite project with Pinia stores and Vue Router
- Login view with session-based auth
- Auth store + route guards (member and admin roles)
- Dashboard view with burndown line chart (Chart.js)
- Issues view with filterable/sortable table and inline time editing
- Members view with grouped bar chart (efficiency analysis)
- Sync status view with history and manual trigger (admin)
- Admin view for user management
- 30-second auto-refresh polling

### Phase 5: Deployment Pipeline ⟨parallel with Phase 4⟩
**Duration**: 3 days  
**Dependencies**: Phase 1  
**Tasks**: T028–T031  
**Deliverables**:
- GitHub Actions workflow (build + SFTP deploy to cPanel)
- cPanel cron job configuration (15-minute sync interval)
- Environment configuration template (.env.example)
- Deployment guide (README.md)

### Phase 6: Polish & Validation
**Duration**: 3 days  
**Dependencies**: All previous phases  
**Tasks**: T032–T036  
**Deliverables**:
- End-to-end integration testing
- Performance optimization (API <200ms target)
- Security review & hardening (OWASP checklist)
- Error handling & resilience for all external dependencies
- Code documentation (PHPDoc + JSDoc)

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