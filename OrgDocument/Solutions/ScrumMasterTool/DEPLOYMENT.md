# Deployment Guide for Production Server

## Subdomain Deployment: `scrum.softercolor.com`

The production site is deployed at:
- **Server Path**: `/home/iafmcqte/public_html/website_98511c15`
- **URL**: `https://scrum.softercolor.com/`
- **Configuration**: Subdomain points directly to the app folder (document root)

## Steps to Deploy

### 1. Build the Frontend for Production

From the `frontend/` directory, run:

```bash
cd frontend
npm run build
```

This builds the Vue app with the default base path (`/`) since the subdomain points to the app root.

### 2. Upload Files to Server

Upload these files/folders to `/home/iafmcqte/public_html/website_98511c15`:

```
├── public/           (entire directory including dist/)
├── src/              (PHP backend source code)
├── config/
├── bootstrap/
├── vendor/           (run composer install on server)
├── .env              (copy from .env.production and update credentials)
```

**Note**: The `.htaccess` file in `public/` is already configured correctly with `RewriteBase /`

### 3. Configure Environment

On the server, in `/home/iafmcqte/public_html/website_98511c15/`:

```bash
# Copy the production environment file
cp .env.production .env

# Edit .env and update:
# - Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
# - APP_KEY (required — generate with `php cron/generate-app-key.php`; see "Admin-managed GitHub accounts")
# - Verify APP_ENV=production
# - Verify APP_DEBUG=false
# - Verify APP_BASE_PATH is empty (for subdomain root)
```

### 4. Install Dependencies

On the server:

```bash
cd /home/iafmcqte/public_html/website_98511c15
composer install --no-dev --optimize-autoloader
```

### 5. Set Permissions

```bash
chmod -R 755 public/
chmod -R 775 data/logs/
chmod 600 .env
```

### 6. Database Setup

Run the database migrations on the server:

```bash
# Import the schema from database/schema.sql
mysql -u scrum_user -p scrum_dashboard < database/schema.sql

# Or run migrations if you have them
```

## Verify Deployment

1. Visit: `https://scrum.softercolor.com/`
2. Assets should load from: `https://scrum.softercolor.com/assets/`
3. API calls should go to: `https://scrum.softercolor.com/api/`

## Troubleshooting

### Assets return 404
- Check `RewriteBase` in `public/.htaccess` is set to `/`
- Check `APP_BASE_PATH` in `.env` is empty or `/`
- Rebuild frontend with `npm run build`

### MIME type errors
- Ensure `AddType` directives are in `public/.htaccess`
- Check mod_mime is enabled on Apache

### Blank page / JS errors
- Check browser console for errors
- Verify `.env` has `APP_BASE_PATH=` (empty)
- Verify Vue build used default base path

## Hours, sprints and burndown (after the 2026-10 update)

Estimated / remaining / actual hours and the sprint come from the GitHub Project fields
`Estimate Hours`, `Remains Hours`, `Actual Hours` (NUMBER) and `Iteration` (ITERATION).
Override the names with `REPO_FIELD_ESTIMATED`, `REPO_FIELD_REMAINING`, `REPO_FIELD_ACTUAL`,
`REPO_FIELD_ITERATION` in the server `.env` if your project uses different names. GitHub is the
source of truth: every sync overwrites the stored hours, the dashboard is read-only, and
closed issues count as 0 remaining in the burndown.

After deploying this version:

1. The deploy pipeline runs `php database/migrate.php` (migrations 009 and 010).
2. Log in as admin and press **Sync Now** (or wait for the cron) — hours and sprints are imported.
3. Repair burndown snapshots that were captured while hours were still 0 (run once, via SSH or the cPanel terminal):
   ```bash
   php cron/rebuild-burndown.php            # all projects
   php cron/rebuild-burndown.php --project=1
   ```
   It deletes snapshots with no estimated hours and captures today's snapshot from the synced data.
4. Open the Dashboard: the sprint selector lists the project's iterations; the chart should show a non-zero ideal line.

## Admin-managed GitHub accounts and multiple projects (Phase 8)

GitHub accounts and their access tokens are now configured in the admin area, not in `.env`.
Tokens are stored encrypted (libsodium, falling back to AES-256-GCM) with `APP_KEY`, the only secret
that stays in `.env`. Several accounts (organizations or users) and several projects are supported.

### Supported size (system limitation)

Up to **10 tracked projects of about 1,000 issues each**. Tracking more shows a warning but is not
blocked; syncs may then exceed shared-hosting request limits or GitHub's hourly GraphQL quota.
All signed-in users can see all tracked projects (per-project access control is a possible future improvement).

### Upgrade runbook (existing installation)

1. **Generate the key** on the server: `php cron/generate-app-key.php`, then add `APP_KEY=<value>` to the server `.env` (`chmod 600 .env`).
   Back the key up somewhere safe: without it the stored tokens cannot be decrypted.
2. **Deploy.** The pipeline runs `php database/migrate.php` (migrations 011 and 012: accounts, audit log, per-project tracking columns).
   Existing projects keep their ids, issues, burndown and history, and become *tracked*.
   Until the import is done, sync keeps using the legacy `REPO_PAT` / `REPO_ORG` / `REPO_PROJECT_NUMBER` values.
3. **Import** the legacy settings (idempotent): `php cron/import-env-account.php`
   or Admin → GitHub accounts → **Import from .env**. The existing project row is linked to the new account.
4. **Test the connection** in Admin → GitHub accounts (status must be *ok*; token expiry and scopes are shown).
5. **Verify sync**: press **Sync Now** for the project and check Sync Status, or run `php cron/sync.php`.
6. **Remove** `REPO_PAT`, `REPO_ORG` and `REPO_PROJECT_NUMBER` from the server `.env`. Keep `REPO_FIELD_*` only if you rely on custom global field names.
7. Add further accounts/projects in the admin area (Admin → GitHub accounts → *Discover projects* → *Track*).

The cron entry stays the same (`php cron/sync.php`): it now syncs every tracked project one after another;
a failing project does not stop the others and the exit code is 1 if any failed. "Sync Now" in the UI syncs the selected project only.

### Rotating or losing `APP_KEY`

- Rotate: `php cron/generate-app-key.php` → `php cron/rotate-app-key.php --old=<current> --new=<new>` → replace `APP_KEY` in `.env`. Re-encryption is atomic.
- Lost key: tokens show status *needs_reentry*; enter each token again in Admin → GitHub accounts.

### Deploy pipeline

The GitHub Actions deploy needs only the SFTP/SSH secrets; it no longer needs any GitHub token.
The SSH key passphrase is passed with the action's `passphrase` input.

## Quick Deploy Script

For future deployments, you can use:

```bash
# On local machine
cd frontend
npm run build
cd ..
# Now upload the public/dist/ folder to server

# On server (via SSH)
cd /home/iafmcqte/public_html/website_98511c15
composer install --no-dev --optimize-autoloader
cp .env.production .env  # (edit with production values)
chmod -R 755 public/
chmod 600 .env
```
