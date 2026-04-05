# T030 — Write Environment Configuration Template

**Task ID**: T030  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 5 — Deployment Pipeline  

### Description
Review and expand `.env.example` to ensure it documents every configuration variable the application reads, with inline comments explaining what each variable does and how to obtain values (particularly the GitHub PAT). The file must be safe to commit — containing only placeholder values, never real credentials.

### Acceptance Criteria
- [x] `.env.example` contains all variables consumed by the application grouped by section
- [x] **Application** group: `APP_ENV`, `APP_DEBUG`, `APP_BASE_PATH`, `APP_URL`
- [x] **Database** group: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- [x] **GitHub API** group: `GITHUB_PAT`, `GITHUB_ORG`, `GITHUB_PROJECT_NUMBER`, `GITHUB_GRAPHQL_URL`; comment explains PAT scopes needed (`read:project`, optionally `read:org`)
- [x] **Session** group: `SESSION_NAME`, `SESSION_SECURE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_LIFETIME`
- [x] **Sync** group: `SYNC_INTERVAL_MINUTES`, `SNAPSHOT_RETENTION_COUNT`
- [x] **Seed / Admin** group: `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` — with comment that these are only read by `database/seed.php`
- [x] All sensitive defaults are clearly marked as placeholders (e.g. `your_db_password`, `ghp_xxxx...`)
- [x] Comment above `GITHUB_PAT` explains: how to generate a PAT at `github.com/settings/tokens`, required scopes, and that it should never be committed
- [x] Production-specific values (`APP_ENV=production`, `SESSION_SECURE=true`) are shown commented-out with inline notes

### Tasks/Subtasks
- [x] Audit all `$_ENV[...]` and `getenv(...)` calls across `src/`, `config/`, `cron/`, `database/` to ensure no variable is missing from `.env.example`
- [x] Add any missing variables found during audit (`GITHUB_PROJECT_NUMBER`, `APP_URL`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`)
- [x] Add `APP_URL` variable with dev default `http://localhost:5173`
- [x] Correct seed var name to `ADMIN_NAME` (matches `database/seed.php` usage)
- [x] Add a prominent header comment block explaining the file's purpose and `DO NOT COMMIT .env`
- [x] Add production callouts (commented `APP_ENV=production`, `SESSION_SECURE=true`)
- [x] Verify `.gitignore` excludes `.env` but tracks `.env.example`

### Definition of Done
- [x] All acceptance criteria met
- [x] No real credentials appear anywhere in `.env.example`
- [x] A developer can spin up the project from scratch using only `.env.example` as a reference
- [x] All variables referenced in `config/settings.php` and `config/container.php` are documented

### Dependencies
- T001 — PHP project initialized (defines which env vars are used)

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
Medium — Required for onboarding new developers and cPanel manual setup

### Labels/Tags
- Category: documentation, devops
- Component: configuration, environment
- Sprint: Phase 5 — Deployment Pipeline

### Notes
- The existing `.env.example` already covers most variables — this task is an audit-and-expand, not a rewrite
- `APP_URL` is needed by T031 (deployment guide) for configuring the cPanel vhost document root
- Source Requirements: R-012

### Progress Updates
- **2026-04-05**: Audited `config/settings.php` — found `GITHUB_PROJECT_NUMBER` missing from old `.env.example`. Noted lock file in `cron/sync.php` is hardcoded (`data/sync.lock`), not env-configurable. Audited `database/seed.php` — uses `ADMIN_NAME` (not `ADMIN_DISPLAY_NAME`). Rewrote `.env.example` with: header block with `DO NOT COMMIT` warning; `APP_URL` added; `GITHUB_PROJECT_NUMBER` added with explanation of where to find the number; PAT generation instructions with required scopes; production callouts for `APP_ENV` and `SESSION_SECURE`; seed section with `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`.

---
**Status**: Completed  
**Last Updated**: 2026-04-05
