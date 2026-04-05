# T028 — Create GitHub Actions Deploy Workflow

**Task ID**: T028  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 5 — Deployment Pipeline  

### Description
Write a GitHub Actions workflow that:
1. Builds the Vue 3 frontend with Vite
2. Installs PHP Composer dependencies
3. Deploys all required files to cPanel shared hosting via SFTP
4. Runs `php database/migrate.php` post-deploy via SSH

The workflow triggers on every push to `main`. SFTP credentials are stored as repository secrets. Unchanged files (notably `vendor/`) are skipped via `lftp mirror --reverse --delete` to keep deploys fast.

### Acceptance Criteria
- [x] `.github/workflows/deploy.yml` exists at the repository root (not inside `OrgDocument/`)
- [x] Workflow triggers on `push` to `main` branch
- [x] Step 1 — Checkout: `actions/checkout@v4`
- [x] Step 2 — Node setup + Vue build: `actions/setup-node@v4` (Node 20), `cd OrgDocument/Solutions/ScrumMasterTool/frontend && npm ci && npm run build`
- [x] Step 3 — PHP setup + Composer install: `shivammathur/setup-php@v2` (PHP 8.2), `cd OrgDocument/Solutions/ScrumMasterTool && composer install --no-dev --optimize-autoloader`
- [x] Step 4 — SFTP deploy: uses `lftp` mirror; uploads: `public/`, `src/`, `config/`, `vendor/`, `cron/`, `database/`, `bootstrap/`
- [x] Secrets used: `SFTP_HOST`, `SFTP_PORT`, `SFTP_USER`, `SFTP_PASSWORD`, `REMOTE_PATH`, `SSH_HOST`, `SSH_USER`, `SSH_KEY`, `SSH_PORT`
- [x] Excludes from upload: `.env`, `data/snapshots/*`, `frontend/*`, `tests/`, `.env.test`, `.env.test.example`
- [x] Step 5 — Post-deploy migration: SSH runs `php {REMOTE_PATH}/database/migrate.php` via `appleboy/ssh-action@v1.0.3`
- [x] Workflow fails fast (each step depends on previous)

### Tasks/Subtasks
- [x] Create `.github/workflows/deploy.yml` at the repository root
- [x] Define `on: push: branches: [main]` trigger (+ `workflow_dispatch` for manual runs)
- [x] Add `setup-node@v4` + `npm ci && npm run build` step with correct working directory
- [x] Add `setup-php@v2` (8.2) + `composer install --no-dev --optimize-autoloader` step
- [x] Add SFTP upload step using `lftp` mirror with all required excludes
- [x] Add SSH post-deploy step to run `php migrate.php` via `appleboy/ssh-action@v1.0.3`
- [x] Document all required secrets in the task notes section

### Definition of Done
- [x] All acceptance criteria met
- [x] Workflow YAML is valid (passes `yamllint` / GitHub Actions parser)
- [ ] A test push to `main` triggers the workflow and deploys successfully (requires live cPanel credentials)
- [x] `public/dist/` (compiled Vue assets) is uploaded as part of `public/`
- [x] `.env` is never uploaded — only `.env.example`

### Dependencies
- T001 — PHP project structure exists (defines folder layout being deployed)
- T004 — Slim 4 entry point in `public/index.php` (confirms `public/` is document root)
- T018 — Vue frontend scaffold exists (`frontend/package.json`, `vite.config.js`)

### Effort Estimate
**Time Estimate**: 1 day

### Priority
High — Unblocks all production deployments

### Labels/Tags
- Category: devops
- Component: ci-cd, deployment, github-actions
- Sprint: Phase 5 — Deployment Pipeline

### Notes
- Workflow file lives at repository root `.github/workflows/deploy.yml`, NOT inside `OrgDocument/`
- The solution root for all paths inside the workflow is `OrgDocument/Solutions/ScrumMasterTool/`
- Use `lftp` for SFTP because it supports incremental mirror uploads — avoids re-uploading all of `vendor/` on every deploy
- `vendor/` should be included in the upload (cPanel shared hosting cannot run `composer install`)
- cPanel typically exposes SSH on a non-standard port — `SFTP_PORT` and `SSH_PORT` secrets should both be configurable
- After deploy, `migrate.php` is idempotent — safe to run on every deploy
- Source Requirements: R-009, R-010, ADR-6

### Progress Updates
- **2026-04-05**: Created `.github/workflows/deploy.yml` at repository root. Steps: (1) `actions/checkout@v4`; (2) `actions/setup-node@v4` node-20 + `npm ci && npm run build` in `frontend/`; (3) `shivammathur/setup-php@v2` php-8.2 + `composer install --no-dev --optimize-autoloader`; (4) `apt-get install lftp` + `lftp` sftp mirror with `--reverse --delete` and excludes for `.env`, `.env.test`, `.env.test.example`, `data/snapshots/*`, `frontend/*`, `tests/*`, `phpunit.xml`, `technical-architecture.md`; (5) `appleboy/ssh-action@v1.0.3` to run `php migrate.php`. `workflow_dispatch` added for manual triggers. Secrets required: `SFTP_HOST`, `SFTP_PORT`, `SFTP_USER`, `SFTP_PASSWORD`, `REMOTE_PATH`, `SSH_HOST`, `SSH_USER`, `SSH_KEY`, `SSH_PORT`.

---
**Status**: Completed  
**Last Updated**: 2026-04-05
