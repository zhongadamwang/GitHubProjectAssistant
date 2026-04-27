# Deployment Guide for Production Server

## Subdirectory Deployment: `/website_98511c15/`

The production site is deployed at:
- **Server Path**: `/home/iafmcqte/public_html/website_98511c15`
- **URL**: `https://yourdomain.com/website_98511c15/`

## Steps to Deploy

### 1. Build the Frontend for Production

From the `frontend/` directory, run:

```bash
cd frontend
npm run build:prod
```

This builds the Vue app with the correct base path (`/website_98511c15/`) so all assets are referenced correctly.

### 2. Upload Files to Server

Upload these files/folders to `/home/iafmcqte/public_html/website_98511c15`:

```
├── public/           (entire directory including dist/)
├── src/              (PHP backend source code)
├── config/
├── bootstrap/
├── vendor/           (run composer install on server)
├── .env              (copy from .env.production and update credentials)
└── .htaccess         (copy from public/.htaccess.production)
```

### 3. Configure Environment

On the server, in `/home/iafmcqte/public_html/website_98511c15/`:

```bash
# Copy the production environment file
cp .env.production .env

# Edit .env and update:
# - Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
# - GitHub PAT (if different)
# - APP_ENV=production
# - APP_DEBUG=false
# - APP_BASE_PATH=/website_98511c15
```

### 4. Configure Apache

In the `public/` directory on the server:

```bash
# Copy production .htaccess
cp .htaccess.production .htaccess
```

This sets `RewriteBase /website_98511c15/` which is required for the subdirectory install.

### 5. Install Dependencies

On the server:

```bash
cd /home/iafmcqte/public_html/website_98511c15
composer install --no-dev --optimize-autoloader
```

### 6. Set Permissions

```bash
chmod -R 755 public/
chmod -R 775 data/logs/
chmod 600 .env
```

### 7. Database Setup

Run the database migrations on the server:

```bash
# Import the schema from database/schema.sql
mysql -u scrum_user -p scrum_dashboard < database/schema.sql

# Or run migrations if you have them
```

## Verify Deployment

1. Visit: `https://yourdomain.com/website_98511c15/`
2. Assets should load from: `https://yourdomain.com/website_98511c15/assets/`
3. API calls should go to: `https://yourdomain.com/website_98511c15/api/`

## Troubleshooting

### Assets return 404
- Check `RewriteBase` in `public/.htaccess` matches `/website_98511c15/`
- Check `APP_BASE_PATH` in `.env` matches `/website_98511c15`
- Rebuild frontend with `npm run build:prod`

### MIME type errors
- Ensure `AddType` directives are in `public/.htaccess`
- Check mod_mime is enabled on Apache

### Blank page / JS errors
- Check browser console for errors
- Verify `.env` has correct `APP_BASE_PATH`
- Verify Vue build used correct `VITE_BASE_PATH`

## Quick Deploy Script

For future deployments, you can use:

```bash
# On local machine
cd frontend
npm run build:prod
cd ..
# Now upload the files to server

# On server (via SSH)
cd /home/iafmcqte/public_html/website_98511c15
composer install --no-dev --optimize-autoloader
cp .env.production .env  # (edit with production values)
cp public/.htaccess.production public/.htaccess
chmod -R 755 public/
chmod 600 .env
```
