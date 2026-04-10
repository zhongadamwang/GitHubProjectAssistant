# Scrum Master Assistant

A GitHub-integrated Scrum dashboard for tracking sprint burndown, issue time estimates, member efficiency, and sync status. Built on PHP 8.2 + Slim 4 (backend REST API) and Vue 3 + Vite (SPA frontend), deployed to cPanel shared hosting.

## Key Features

- **Sprint Burndown Chart** — ideal vs. actual remaining-effort line chart, updated every 15 minutes from GitHub
- **Issue Time Tracking** — inline editing of estimated / remaining / actual hours per issue
- **Member Efficiency Analysis** — grouped bar chart comparing estimated vs. actual effort by team member
- **Sync Monitoring** — sync history table, status indicator (ok / stale / error), manual "Sync Now" trigger for admins
- **User Management** — admin panel to create and list team members
- **Auto-Refresh** — dashboard polls every 30 s; issues list polls every 60 s

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [Prerequisites](#prerequisites)
3. [Local Development](#local-development)
4. [cPanel Manual Deployment](#cpanel-manual-deployment)
5. [GitHub Actions Automated Deployment](#github-actions-automated-deployment)
6. [Troubleshooting](#troubleshooting)
7. [Architecture Reference](#architecture-reference)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend API | PHP 8.2 · Slim 4 · PHP-DI (DI container) |
| Database | MySQL 5.7+ (6 tables) |
| Frontend | Vue 3 · Vite 5 · Pinia · Vue Router 4 |
| Charts | Chart.js 4 |
| HTTP Client | Axios (withCredentials, 401 interceptor) |
| GitHub Integration | GitHub GraphQL API v4 · Personal Access Token |
| Deployment | GitHub Actions · lftp SFTP · cPanel SSH |
| Cron | cPanel Cron Jobs (`*/15 * * * *`) |

---

## Prerequisites

| Requirement | Minimum Version | Notes |
|---|---|---|
| PHP | 8.2 | Extensions: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `posix` |
| Composer | 2.x | `composer --version` |
| Node.js | 20 LTS | `node --version` |
| npm | 9+ | bundled with Node 20 |
| MySQL | 5.7 | or MariaDB 10.4+ |
| cPanel hosting | — | SSH access, SFTP access, Cron Jobs panel |
| GitHub account | — | PAT with `read:project`, `read:org`, `read:user` scopes |

---

## Local Development

```bash
# 1. Clone the repository
git clone https://github.com/<your-org>/<your-repo>.git
cd <your-repo>/OrgDocument/Solutions/ScrumMasterTool

# 2. Copy the environment template and fill in values
cp .env.example .env
# Open .env and set DB_*, GITHUB_TOKEN, GITHUB_ORG, GITHUB_PROJECT_NUMBER

# 3. Install PHP dependencies
composer install

# 4. Create the database (from MySQL client)
#    CREATE DATABASE scrum_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 5. Run migrations (creates all 6 tables)
php database/migrate.php

# 6. Seed the admin user (uses ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME from .env)
php database/seed.php

# 7. Start the PHP development server (API on port 8080)
php -S localhost:8080 -t public/

# 8. In a second terminal — start the Vue dev server (proxies /api → localhost:8080)
cd frontend
npm install
npm run dev
# Access the app at http://localhost:5173
```

> The Vue dev server is preconfigured to proxy all `/api/*` requests to `http://localhost:8080`, so there are no CORS issues in development.

---

## cPanel Manual Deployment

### Step 1 — Create a MySQL Database

1. Log in to cPanel → **MySQL Databases**
2. Create a new database (e.g. `cpanelusername_scrum`)
3. Create a MySQL user and assign **All Privileges** to that database
4. Note the database name, username, password, and host (`localhost`)

### Step 2 — Upload Files via SFTP

Connect to your cPanel account via SFTP and upload the entire `ScrumMasterTool/` directory contents to your target path (e.g. `~/public_html/scrum/`).

**Do NOT upload the following:**

| Exclude | Reason |
|---|---|
| `.env` | Contains secrets — created on server manually |
| `frontend/` | Source only — built assets already in `public/dist/` |
| `tests/` | Not needed on production |
| `phpunit.xml` | Dev tool config |
| `data/snapshots/*` | Generated data — preserve any existing snapshots |

> **Tip**: Build the frontend locally first (`cd frontend && npm run build`) so that `public/dist/` is included in the upload.

### Step 3 — Create the `.env` File on the Server

SSH into your cPanel account, navigate to the project root, and create `.env`:

```bash
cd ~/public_html/scrum          # adjust to your REMOTE_PATH
cp .env.example .env
nano .env                       # fill in production values (see comments in .env.example)
chmod 600 .env                  # restrict permissions
```

Set `APP_ENV=production` and `SESSION_SECURE=true` for production.

### Step 4 — Run Migrations

```bash
php database/migrate.php
```

The migration runner is idempotent — it is safe to run multiple times.

### Step 5 — Seed the Admin User (first deploy only)

```bash
php database/seed.php
# Or override via CLI:
# ADMIN_EMAIL=you@example.com ADMIN_PASSWORD=secret ADMIN_NAME="Your Name" php database/seed.php
```

### Step 6 — Set Document Root

In cPanel → **Domains** (or **Addon Domains**), set the document root to:

```
public_html/scrum/public
```

This ensures only `public/index.php` and `public/dist/` are web-accessible.

### Step 7 — Configure Cron Job

In cPanel → **Cron Jobs**, add an entry:

| Field | Value |
|---|---|
| Minute | `*/15` |
| Hour | `*` |
| Day | `*` |
| Month | `*` |
| Weekday | `*` |
| Command | `php /home/<cpanelusername>/public_html/scrum/cron/sync.php >> /home/<cpanelusername>/logs/sync.log 2>&1` |

> Run `bash cron/setup.sh` on the server to auto-detect the correct PHP binary path and print the exact command to copy into cPanel.

---

## GitHub Actions Automated Deployment

Every push to the `main` branch triggers the workflow (`.github/workflows/deploy.yml`). It can also be triggered manually from the **Actions** tab → **Deploy to cPanel** → **Run workflow**.

### Workflow Steps

| Step | What It Does |
|---|---|
| 1. Checkout | Clones the repository |
| 2. Build frontend | `npm ci && npm run build` — outputs to `public/dist/` |
| 3. Composer install | `--no-dev --optimize-autoloader` |
| 4. SFTP deploy | lftp incremental mirror to `REMOTE_PATH` (excludes `.env`, `tests/`, `frontend/`, `data/snapshots/*`) |
| 5. Post-deploy SSH | Runs `php migrate.php`, creates `~/logs/` + `data/snapshots/`, prints cron command |

### Required Repository Secrets

Go to **Settings → Secrets and variables → Actions → New repository secret** and add each of the following:

| Secret Name | Description | Example |
|---|---|---|
| `SFTP_HOST` | cPanel hostname (SFTP) | `ftp.yourdomain.com` |
| `SFTP_PORT` | SFTP port (usually 21 or 22) | `21` |
| `SFTP_USER` | cPanel username | `cpanelusername` |
| `SFTP_PASSWORD` | cPanel account password | *(your cPanel password)* |
| `REMOTE_PATH` | Absolute path to the app on the server | `/home/cpanelusername/public_html/scrum` |
| `SSH_HOST` | SSH hostname | `yourdomain.com` |
| `SSH_USER` | SSH username (same as cPanel username) | `cpanelusername` |
| `SSH_KEY` | Private SSH key (PEM format) — add the **public** key to cPanel → SSH Access | *(contents of `~/.ssh/id_rsa`)* |
| `SSH_PORT` | SSH port | `22` |

### Viewing Deployment Logs

1. Navigate to **Actions** tab in your GitHub repository
2. Click on the latest **Deploy to cPanel** workflow run
3. Expand any step to view detailed output

### Manual Re-deploy

In the **Actions** tab → **Deploy to cPanel** → **Run workflow** → select `main` → **Run workflow**.

---

## Troubleshooting

### Login fails or session does not persist

**Cause**: `SESSION_SECURE=true` requires HTTPS. In local development the cookie is dropped over plain HTTP.  
**Fix**: Set `SESSION_SECURE=false` in `.env` for local development. On production, ensure the site is served over HTTPS before setting `SESSION_SECURE=true`.

Also verify `SESSION_DOMAIN` matches the hostname you are accessing (leave blank to default to the current host).

---

### CORS error in browser console

**Cause**: `APP_URL` in `.env` does not match the origin the browser is using.  
**Fix**: Set `APP_URL` to the exact scheme + host + (optional port) from the browser address bar, e.g. `APP_URL=https://yourdomain.com`. The Slim CORS middleware uses this value to set `Access-Control-Allow-Origin`.

---

### GitHub sync returns HTTP 401

**Cause**: The GitHub Personal Access Token has expired or is missing required scopes.  
**Fix**:
1. Go to **GitHub → Settings → Developer settings → Personal access tokens**
2. Regenerate the token and ensure it has `read:project`, `read:org`, and `read:user` scopes
3. Update `GITHUB_TOKEN` in `.env` on the server (and re-run `php cron/sync.php` manually to verify)

---

### Burndown chart shows no data

**Cause**: The cron job has not run yet, so `burndown_daily` table is empty.  
**Fix**: Trigger a manual sync via SSH:

```bash
php /home/<cpanelusername>/public_html/scrum/cron/sync.php
```

Or click **Sync Now** in the app (admin role required). After a successful sync, reload the dashboard.

---

### GitHub API rate limit exceeded

**Cause**: The GraphQL API allows 5,000 points per hour per PAT. High-frequency polling or large projects can exhaust this quickly.  
**Fix**:
1. Check current consumption in the `sync_history` table: `SELECT points_used, created_at FROM sync_history ORDER BY created_at DESC LIMIT 10;`
2. Increase the cron interval (e.g. `*/30` instead of `*/15`) in cPanel → Cron Jobs
3. If the project has many issues, consider reducing pagination depth in `src/GraphQL/Queries.php`

---

### Uploaded files but app shows blank page or 404

**Cause**: Document root not pointing to `public/`, or `public/dist/` is missing (frontend was not built before upload).  
**Fix**:
1. Verify document root is set to `.../scrum/public` in cPanel → Domains
2. Rebuild the frontend locally (`npm run build` in `frontend/`) and re-upload `public/dist/`
3. Confirm `public/.htaccess` was uploaded (enables SPA fallback routing via `mod_rewrite`)

---

## Architecture Reference

For full system design decisions, database schema, API contract, and ADRs see:

- [`technical-architecture.md`](technical-architecture.md) — solution-level technical architecture (7 ADRs, sequence diagrams, DB schema)
- [`OrgDocument/orgModel/solutions.md`](../../orgModel/solutions.md) — organisational model context
- [`OrgDocument/projects/01 - Scrum master assistant/`](../../projects/01%20-%20Scrum%20master%20assistant/) — project plan, task tracking, requirements, collaboration diagrams
