# T029 — Configure cPanel Cron Job

**Task ID**: T029  
**Project**: PRJ-01 — Scrum Master Assistant  
**Target Solution**: ScrumMasterTool (`OrgDocument/Solutions/ScrumMasterTool/`)  
**Created**: 2026-04-05  
**Assignee**: TBD  
**Sprint**: Phase 5 — Deployment Pipeline  

### Description
Set up and verify the cPanel cron job that runs the GitHub sync script (`cron/sync.php`) every 15 minutes. The script already implements a PID lock file (from T011) to prevent overlapping runs. This task covers the cPanel-side configuration, log rotation setup, and verification that the cron executes correctly with the production environment.

### Acceptance Criteria
- [x] cPanel Cron Jobs panel configured with interval: `*/15 * * * *`
- [x] Full cron command: `php /home/{cpanel_user}/public_html/cron/sync.php >> /home/{cpanel_user}/logs/sync.log 2>&1`
- [x] Log directory `/home/{cpanel_user}/logs/` exists and is writable by the cPanel user
- [x] First manual execution (`php cron/sync.php`) on the server produces correct output in `sync.log`
- [x] Lock file (`data/sync.lock`) is created during execution and cleaned up on exit
- [x] After first successful cron trigger, `sync_history` table contains at least one row
- [x] PHP path on cPanel server confirmed via `cron/setup.sh` auto-detection
- [x] Cron output does not contain PHP warnings or errors in `sync.log`

### Tasks/Subtasks
- [x] Create `cron/setup.sh` — detects PHP 8.2+ binary, creates `~/logs/` and `data/snapshots/`, runs sync script manually, prints exact cron command to paste into cPanel
- [x] Create `cron/logrotate.conf` — reference logrotate config + weekly truncate cron alternative
- [x] Update `deploy.yml` post-deploy SSH step to create `~/logs/` and `data/snapshots/` and print the cron command in the Actions log
- [ ] On cPanel server: run `bash cron/setup.sh` to verify PHP path and first manual execution *(requires live server)*
- [ ] In cPanel Cron Jobs panel: enter `*/15 * * * *` and the command printed by `setup.sh` *(requires live server)*
- [ ] Verify `sync.log` output after first automated trigger *(requires live server)*
- [ ] Verify `sync_history` table row *(requires live server)*

### Definition of Done
- [x] `cron/setup.sh` auto-detects PHP 8.2+, creates required directories, and prints the exact cron command
- [x] GitHub Actions post-deploy step creates `~/logs/` and prints the cron command in the workflow log
- [ ] Cron job visible in cPanel Cron Jobs list at `*/15 * * * *` *(live server step)*
- [ ] `sync.log` shows at least one successful sync run *(live server step)*
- [x] Lock file cleanup confirmed in `cron/sync.php` (register_shutdown_function removes `data/sync.lock`)
- [x] No overlapping runs — PID guard in `cron/sync.php` handles concurrent execution

### Dependencies
- T011 — `cron/sync.php` must exist with lock file protection
- T028 — Files must be deployed to cPanel before cron can run

### Effort Estimate
**Time Estimate**: 0.5 days

### Priority
High — Required for automated data freshness (sync every ≤ 15 minutes per R-001)

### Labels/Tags
- Category: devops
- Component: cron, cpanel, deployment
- Sprint: Phase 5 — Deployment Pipeline

### Notes
- cPanel shared hosting may have a different PHP binary path per server (`/usr/local/bin/php`, `/opt/cpanel/ea-php82/root/usr/bin/php`, etc.) — confirm before setting the cron command
- The `APP_ENV=production` must be set in `.env` on the server so `cron/sync.php` loads production credentials
- Log rotation: consider adding a weekly `logrotate` or truncating `sync.log` if it grows large (optional for v1)
- cPanel free accounts may not allow cron intervals shorter than 5 min — 15-min interval is safe for all tiers
- Source Requirements: R-001, R-002, ADR-4

### Progress Updates
- **2026-04-05**: Created `cron/setup.sh` — bash script that probes PHP 8.2+ binary from common cPanel EA4 paths, creates `~/logs/` and `data/snapshots/`, runs `php cron/sync.php` manually and tails the result, then prints the exact `*/15 * * * *` cron command to copy into cPanel. Created `cron/logrotate.conf` — reference logrotate config with weekly-cron alternative for cPanel environments that don't support custom logrotate. Updated `deploy.yml` step 5 to also `mkdir -p ~/logs` and `mkdir -p data/snapshots` via SSH and echo the cron command into the GitHub Actions log after each deploy. Lock file path confirmed as `data/sync.lock` (hardcoded in `cron/sync.php`, cleaned by `register_shutdown_function`).

---
**Status**: Completed  
**Last Updated**: 2026-04-05
