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
- [ ] `.github/workflows/deploy.yml` exists at the repository root (not inside `OrgDocument/`)
- [ ] Workflow triggers on `push` to `main` branch
- [ ] Step 1 — Checkout: `actions/checkout@v4`
- [ ] Step 2 — Node setup + Vue build: `actions/setup-node@v4` (Node 20), `cd OrgDocument/Solutions/ScrumMasterTool/frontend && npm ci && npm run build`
- [ ] Step 3 — PHP setup + Composer install: `shivammathur/setup-php@v2` (PHP 8.2), `cd OrgDocument/Solutions/ScrumMasterTool && composer install --no-dev --optimize-autoloader`
- [ ] Step 4 — SFTP deploy: uses `lftp` via `appleboy/ssh-action` or direct `lftp` step; uploads only the following subdirectories: `public/`, `src/`, `config/`, `vendor/`, `cron/`, `database/`, `bootstrap/`
- [ ] Secrets used: `SFTP_HOST`, `SFTP_PORT`, `SFTP_USER`, `SFTP_PASSWORD`, `REMOTE_PATH`, `SSH_HOST`, `SSH_USER`, `SSH_KEY`
- [ ] Excludes from upload: `.env`, `data/snapshots/*.json`, `frontend/` (source), `frontend/node_modules/`, `tests/`, `.env.test`
- [ ] Step 5 — Post-deploy migration: SSH into cPanel server and runs `php {REMOTE_PATH}/database/migrate.php`
- [ ] Workflow fails fast (each step depends on previous)

### Tasks/Subtasks
- [ ] Create `.github/workflows/deploy.yml` at the repository root
- [ ] Define `on: push: branches: [main]` trigger
- [ ] Add `setup-node@v4` + `npm ci && npm run build` step with correct working directory
- [ ] Add `setup-php@v2` (8.2) + `composer install --no-dev --optimize-autoloader` step
- [ ] Add SFTP upload step using `lftp` mirror: `lftp -e "mirror --reverse --delete --exclude='.env' --exclude='tests/' --exclude='frontend/' --exclude='data/snapshots/' /path/to/src/ {REMOTE_PATH}; bye" sftp://{USER}:{PASS}@{HOST}`
- [ ] Add SSH post-deploy step to run `php migrate.php`
- [ ] Document all required secrets in the task notes section

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Workflow YAML is valid (passes `yamllint` / GitHub Actions parser)
- [ ] A test push to `main` triggers the workflow and deploys successfully
- [ ] `public/dist/` (compiled Vue assets) is uploaded as part of `public/`
- [ ] `.env` is never uploaded — only `.env.example`

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
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
