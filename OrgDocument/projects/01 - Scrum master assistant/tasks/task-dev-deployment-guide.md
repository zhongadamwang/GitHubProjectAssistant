# T031 — Write Deployment Guide

**Task ID**: T031  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 5 — Deployment Pipeline  

### Description
Write a comprehensive `README.md` at the solution root (`OrgDocument/Solutions/ScrumMasterTool/README.md`) covering project overview, local development setup, cPanel manual deployment, GitHub Actions automated deployment, and troubleshooting. This is the primary reference document for anyone setting up or operating the system.

### Acceptance Criteria
- [x] `OrgDocument/Solutions/ScrumMasterTool/README.md` exists and is structured with clear H2 sections
- [x] **Project Overview** section: what the tool does, key features, screenshot or diagram reference
- [x] **Tech Stack** section: PHP 8.2 + Slim 4, MySQL, Vue 3 + Vite + Chart.js, GitHub GraphQL v4, cPanel
- [x] **Prerequisites** section: PHP 8.2, Composer, Node 20, MySQL 5.7+, cPanel hosting with SSH/SFTP/Cron access, GitHub account with PAT
- [x] **Local Development** section:
  - Clone repo
  - `cd OrgDocument/Solutions/ScrumMasterTool`
  - `cp .env.example .env` and fill in values
  - `composer install`
  - `php database/migrate.php` (run migrations)
  - `php database/seed.php` (seed admin user)
  - `php -S localhost:8080 -t public/` (PHP dev server)
  - `cd frontend && npm install && npm run dev` (Vue dev server with proxy)
  - Access at `http://localhost:5173`
- [x] **cPanel Manual Deployment** section (step-by-step):
  1. Create MySQL database and user in cPanel → MySQL Databases
  2. Upload files via SFTP (which dirs to upload, what to exclude)
  3. SSH in and copy `.env.example` → `.env`, fill in production values
  4. Run `php database/migrate.php`
  5. Run `php database/seed.php` (first time only)
  6. Set document root to `public/` in cPanel → Domains
  7. Configure cron job (`*/15 * * * * php .../cron/sync.php >> .../logs/sync.log 2>&1`)
- [x] **GitHub Actions Automated Deployment** section:
  - List all required repository secrets with descriptions
  - How to add secrets (`Settings → Secrets → Actions`)
  - Workflow trigger behavior and how to view logs
  - How to trigger a manual re-deploy
- [x] **Troubleshooting** section with at least these entries:
  - Login fails / session not persisting → check `SESSION_SECURE=false` in dev, PHP session config
  - CORS error in browser → check `APP_URL` and Slim CORS middleware origin setting
  - GitHub sync returns 401 → PAT expired or missing `read:project` scope  
  - Burndown chart shows no data → cron has not run yet; trigger `php cron/sync.php` manually
  - API rate limit exceeded (5000 pts/hr) → check `sync_history.points_used`; reduce sync frequency
- [x] **Architecture Reference** section: link to `technical-architecture.md`

### Tasks/Subtasks
- [x] Create `OrgDocument/Solutions/ScrumMasterTool/README.md`
- [x] Write **Project Overview** section (2–3 paragraphs + feature bullet list)
- [x] Write **Tech Stack** section (table format)
- [x] Write **Prerequisites** section
- [x] Write **Local Development** section with numbered steps and exact commands
- [x] Write **cPanel Manual Deployment** section with numbered steps
- [x] Write **GitHub Actions Automated Deployment** section (secrets table + workflow description)
- [x] Write **Troubleshooting** section (problem → cause → fix format)
- [x] Write **Architecture Reference** section with relative link
- [x] Verify all commands in the guide are accurate against the actual codebase

### Definition of Done
- [x] All acceptance criteria met
- [x] A developer with no prior context can successfully run the project locally by following the guide
- [x] All secrets mentioned in T028 are documented in the GitHub Actions section (9 secrets: SFTP_HOST/PORT/USER/PASSWORD, REMOTE_PATH, SSH_HOST/USER/KEY/PORT)
- [x] No placeholder `TODO` sections remain

### Dependencies
- T028 — GitHub Actions workflow must exist to document it accurately
- T029 — Cron setup must be known to document the exact command

### Effort Estimate
**Time Estimate**: 1 day

### Priority
Medium — Required for handoff and production operations

### Labels/Tags
- Category: documentation
- Component: readme, deployment, operations
- Sprint: Phase 5 — Deployment Pipeline

### Notes
- Write for an audience that knows PHP/Vue but may not be familiar with cPanel or Slim 4
- Keep commands copy-paste ready — avoid wrapping long commands across lines
- The `cd frontend && npm run build` step is run automatically by GitHub Actions but must also be documented for manual deploys
- Source Requirements: R-012

### Progress Updates
- **2026-04-05**: Created `OrgDocument/Solutions/ScrumMasterTool/README.md` with all required sections: Project Overview (features list), Tech Stack (table), Prerequisites (table), Local Development (8 copy-paste steps), cPanel Manual Deployment (7 steps incl. SFTP exclude list), GitHub Actions (workflow steps + 9-secret table + manual trigger instructions), Troubleshooting (6 entries: login/sessions, CORS, 401 GitHub, burndown empty, rate limit, blank page), Architecture Reference (relative links to technical-architecture.md and org model). All commands verified against live codebase.

---
**Status**: Completed  
**Last Updated**: 2026-04-05
