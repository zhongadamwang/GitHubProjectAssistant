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
- [ ] `.env.example` contains all variables consumed by the application grouped by section
- [ ] **Application** group: `APP_ENV`, `APP_DEBUG`, `APP_BASE_PATH`, `APP_URL`
- [ ] **Database** group: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- [ ] **GitHub API** group: `GITHUB_PAT`, `GITHUB_ORG`, `GITHUB_GRAPHQL_URL`; comment explains PAT scopes needed (`read:project`, optionally `read:org`)
- [ ] **Session** group: `SESSION_NAME`, `SESSION_SECURE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_LIFETIME`
- [ ] **Sync** group: `SYNC_INTERVAL_MINUTES`, `SYNC_LOCK_FILE`
- [ ] **Seed / Admin** group: `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_DISPLAY_NAME` — with comment that these are only read by `database/seed.php`
- [ ] All sensitive defaults are clearly marked as placeholders (e.g. `your_db_password`, `ghp_xxxx...`)
- [ ] Comment above `GITHUB_PAT` explains: how to generate a PAT at `github.com/settings/tokens`, required scopes, and that it should never be committed
- [ ] Production-specific values (`APP_ENV=production`, `SESSION_SECURE=true`) are shown commented-out or clearly documented

### Tasks/Subtasks
- [ ] Audit all `$_ENV[...]` and `getenv(...)` calls across `src/`, `config/`, `cron/`, `database/` to ensure no variable is missing from `.env.example`
- [ ] Add any missing variables found during audit
- [ ] Add `APP_URL` variable (used by GitHub Actions redirect or absolute URL generation)
- [ ] Add `ADMIN_DISPLAY_NAME` if missing (used by `database/seed.php`)
- [ ] Ensure `SYNC_LOCK_FILE` is documented (defaults to `/tmp/scrum_sync.lock`)
- [ ] Add a prominent `# DO NOT COMMIT .env — this file is .env.example only` header comment
- [ ] Verify `.gitignore` excludes `.env` but tracks `.env.example`

### Definition of Done
- [ ] All acceptance criteria met
- [ ] No real credentials appear anywhere in `.env.example`
- [ ] A developer can spin up the project from scratch using only `.env.example` as a reference
- [ ] All variables referenced in `config/settings.php` and `config/container.php` are documented

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
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
