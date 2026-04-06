# T032 — End-to-End Integration Testing

**Task ID**: T032  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-06  
**Assignee**: TBD  
**Sprint**: Phase 6 — Polish & Validation  

### Description
Validate the complete application stack end-to-end: from GitHub sync → DB storage → REST API → Vue frontend. Covers all critical user flows (login, dashboard, issues, members, sync status, admin) against a fully-deployed or locally-running instance. The goal is a confidence gate that confirms all phases work together before performance and security hardening.

### Acceptance Criteria
- [ ] All 13 API endpoints return correct status codes and payload shapes when tested with `curl` or a REST client (documented in a `tests/e2e/api-smoke.sh` script)
- [ ] Login flow: `POST /api/auth/login` with valid credentials → 200 + session cookie; repeated call → still 200; `POST /api/auth/logout` → 200; subsequent `GET /api/auth/me` → 401
- [ ] Member access control: authenticated member can `GET /api/projects`, `GET /api/projects/{id}/burndown`, `GET /api/projects/{id}/issues`, `GET /api/projects/{id}/members`; `POST /api/admin/users` returns 403
- [ ] Admin access control: admin can `GET /api/admin/users`, `POST /api/admin/users`, `POST /api/sync/trigger`
- [ ] Time update flow: `PATCH /api/issues/{id}/time` with `estimated_hours`, `remaining_hours`, `actual_hours` → 200; re-fetch issue shows updated values; values survive a subsequent sync run (time fields not overwritten)
- [ ] Burndown data integrity: after at least one `BurndownService::captureDaily()` run, `GET /api/projects/{id}/burndown` returns `ideal` and `actual` arrays each with at least one point
- [ ] Efficiency data integrity: `GET /api/projects/{id}/members` returns array of members with `estimated_hours`, `actual_hours`, `ratio` fields
- [ ] Sync trigger: `POST /api/sync/trigger` (admin) returns 200; `sync_history` table gains a new row; `GET /api/sync/history` reflects the new record
- [ ] Frontend smoke: compiled `public/dist/index.html` loads in browser; Vue Router navigates to `/login`, `/dashboard`, `/issues`, `/members`, `/sync`; no console errors on happy paths
- [ ] PHPUnit test suite passes clean: `composer test` exits 0 with no skipped tests that hide failures

### Tasks/Subtasks
- [ ] Write `tests/e2e/api-smoke.sh` — `curl` script covering all 13 endpoints with expected HTTP status assertions; requires `TEST_BASE_URL`, `TEST_ADMIN_EMAIL`, `TEST_ADMIN_PASSWORD` env vars  
- [ ] Run smoke script against local dev instance; fix any endpoint-level regressions found  
- [ ] Run `composer test` and confirm all existing PHPUnit suites pass (Phase1–Phase3 unit + Phase2 integration)  
- [ ] Manually exercise Vue frontend: login → dashboard (burndown chart renders) → issues (inline edit works) → members (bar chart renders) → sync status (history table populates) → admin view (user list loads)  
- [ ] Verify 30-second auto-refresh: leave dashboard open for 60 seconds and confirm network tab shows re-fetch at ~30s intervals  
- [ ] Test session expiry: manually clear PHP session storage on server; confirm unauthenticated API calls return 401 and frontend redirects to `/login`  
- [ ] Test admin-only POST `/api/sync/trigger` as member → confirm 403  
- [ ] Test `PATCH /api/issues/{id}/time` survives a sync: run sync after patching; re-check time values unchanged  
- [ ] Document any discovered bugs in a `tests/e2e/FINDINGS.md` with severity (blocker / major / minor)  
- [ ] Fix all blocker-severity findings before marking T032 complete  

### Definition of Done
- [ ] All acceptance criteria met  
- [ ] `tests/e2e/api-smoke.sh` exits 0 against a running instance  
- [ ] `composer test` exits 0  
- [ ] No blocker-severity findings open  
- [ ] `tests/e2e/FINDINGS.md` created (may list minor/major items as known issues for T033–T035)  

### Dependencies
- All Phase 1 tasks (T001–T006): backend + auth + DB  
- All Phase 2 tasks (T007–T012): GitHub sync pipeline  
- All Phase 3 tasks (T013–T017): analytics engine  
- All Phase 4 tasks (T018–T027): Vue frontend  
- All Phase 5 tasks (T028–T031): deployment pipeline  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High — This is the entry gate for T033–T036; all polish and hardening tasks depend on it  

### Labels/Tags
- Category: testing  
- Component: full-stack, e2e  
- Sprint: Phase 6 — Polish & Validation  

### Notes
- Run the smoke script against `APP_ENV=production` settings (not dev) to catch any environment-specific issues  
- Use `php -S localhost:8080 -t public/` for local testing if a cPanel staging environment is not available  
- Source Requirements: R-001 through R-012 (covers all functional requirements)  

### Progress Updates

---
**Status**: Not Started  
**Last Updated**: 2026-04-06
