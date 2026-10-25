# T053 — Backward Compatibility: Import from `.env` and Deployment Changes

**Task ID**: T053  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-10-24  
**Assignee**: TBD  
**Sprint**: Phase 8 — Admin Configuration & Multi-Project  
**Change Reference**: [REQ-ADD-002](../artifacts/Changes/2026-10-24-REQ-ADD-002-admin-github-account-config.md), [REQ-ADD-003](../artifacts/Changes/2026-10-24-REQ-ADD-003-multi-project-support.md)  

### Description
Upgrade existing deployments without data loss or downtime: move the `.env` GitHub settings into an account + tracked project, then stop depending on them.

### Acceptance Criteria
- [x] `POST /api/admin/github-accounts/import-env` (and `php cron/import-env-account.php`) creates an account from `REPO_ORG` + `REPO_PAT` (token encrypted), detects owner type, and links the existing `projects` row(s) for `REPO_ORG` / `REPO_PROJECT_NUMBER` as tracked; idempotent
- [x] Runtime fallback: if no account exists, `cron/sync.php` and Sync Now keep using the legacy `.env` values (with a logged deprecation notice) so an upgrade before importing does not break sync
- [x] After import the legacy values are ignored; documentation says they can be removed from the server `.env`
- [x] `APP_KEY` added to `.env.example`/`.env.production`; `DEPLOYMENT.md` gets the upgrade runbook (generate key → deploy → migrations → import → test connection → verify sync → remove `REPO_PAT`); the GitHub Actions workflow no longer needs a GitHub token secret and the deploy pipeline also fixes the `key_passphrase` → `passphrase` input warning
- [x] Existing issues, burndown, history and iterations remain attached to the same project id (verified on a copy of production data)
- [x] PHPUnit for import (idempotent, missing values, existing account) and fallback

### Tasks/Subtasks
- [x] Import endpoint + CLI; fallback path; docs; workflow tweak
- [x] Upgrade rehearsal on a copy of the production database

### Definition of Done
- [x] All acceptance criteria met; rehearsal documented  

### Dependencies
- T048, T051  

### Effort Estimate
**Time Estimate**: 1 day  

### Priority
High  

### Progress Updates
- **2026-10-25**: Import endpoint + CLI, legacy fallback with deprecation notice, DEPLOYMENT runbook, workflow `passphrase` fix and upgrade rehearsal done. Note: `APP_KEY` was added to `.env.example` only — it was deliberately NOT added to the tracked `.env.production` (a real key must never be committed; set it on the server).

---
**Status**: Completed  
**Last Updated**: 2026-10-25
