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
- [ ] cPanel Cron Jobs panel configured with interval: `*/15 * * * *`
- [ ] Full cron command: `php /home/{cpanel_user}/public_html/cron/sync.php >> /home/{cpanel_user}/logs/sync.log 2>&1`
- [ ] Log directory `/home/{cpanel_user}/logs/` exists and is writable by the cPanel user
- [ ] First manual execution (`php cron/sync.php`) on the server produces correct output in `sync.log`
- [ ] Lock file (`/tmp/scrum_sync.lock` or equivalent) is created during execution and cleaned up on exit
- [ ] After first successful cron trigger, `sync_history` table contains at least one row
- [ ] PHP path on cPanel server confirmed (use `which php` or `/usr/local/bin/php` if needed)
- [ ] Cron output does not contain PHP warnings or errors in `sync.log`

### Tasks/Subtasks
- [ ] Log into cPanel → Cron Jobs panel
- [ ] Verify PHP binary path on cPanel: run `which php` via cPanel Terminal or SSH
- [ ] Create log directory: `mkdir -p ~/logs && chmod 755 ~/logs`
- [ ] Enter the cron command with the correct absolute paths for `php` binary and `sync.php`
- [ ] Save and wait for first automated execution (or trigger manually via SSH)
- [ ] Inspect `sync.log` to confirm successful output
- [ ] Verify `sync_history` table has a new row with `status = 'success'`
- [ ] Document the exact cron command used (with real paths) in a `DEPLOYMENT.md` note or cPanel screenshot

### Definition of Done
- [ ] All acceptance criteria met
- [ ] Cron job visible in cPanel Cron Jobs list at `*/15 * * * *`
- [ ] `sync.log` shows at least one successful sync run
- [ ] Lock file cleanup confirmed (file absent after sync completes)
- [ ] No overlapping runs under normal conditions

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
_(none yet)_

---
**Status**: Not Started  
**Last Updated**: 2026-04-05
