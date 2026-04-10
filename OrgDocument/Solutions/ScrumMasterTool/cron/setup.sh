#!/usr/bin/env bash
# =============================================================================
# cron/setup.sh — cPanel cron job configuration helper
#
# Run this script on the cPanel server (via SSH Terminal) to:
#   1. Detect the correct PHP 8.2 binary path
#   2. Create the log directory
#   3. Print the exact cron command to paste into cPanel Cron Jobs
#   4. Verify the sync script runs without errors
#
# Usage (on the cPanel server via SSH):
#   cd /home/<cpanel_user>/public_html
#   bash cron/setup.sh
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$HOME/logs"
LOG_FILE="$LOG_DIR/sync.log"
SYNC_SCRIPT="$APP_ROOT/cron/sync.php"

# ─────────────────────────────────────────────────────────────────────────────
# 1. Detect PHP 8.2+ binary
# ─────────────────────────────────────────────────────────────────────────────
echo "=== Detecting PHP binary ==="

PHP_BIN=""

# Common cPanel EA4 PHP 8.2 paths (checked in order of preference)
CANDIDATES=(
    "/usr/local/bin/php"
    "/opt/cpanel/ea-php82/root/usr/bin/php"
    "/usr/bin/php82"
    "/usr/bin/php8.2"
    "/usr/bin/php"
    "$(which php 2>/dev/null || true)"
)

for candidate in "${CANDIDATES[@]}"; do
    if [[ -x "$candidate" ]]; then
        version=$("$candidate" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0")
        major=$(echo "$version" | cut -d. -f1)
        minor=$(echo "$version" | cut -d. -f2)
        if [[ "$major" -ge 8 && "$minor" -ge 2 ]]; then
            PHP_BIN="$candidate"
            echo "  Found PHP $version at: $PHP_BIN"
            break
        else
            echo "  Skipping $candidate (PHP $version — need 8.2+)"
        fi
    fi
done

if [[ -z "$PHP_BIN" ]]; then
    echo ""
    echo "ERROR: No PHP 8.2+ binary found. Please contact your hosting provider"
    echo "or specify the path manually: PHP_BIN=/path/to/php bash cron/setup.sh"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# 2. Create log directory
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "=== Creating log directory ==="
mkdir -p "$LOG_DIR"
chmod 755 "$LOG_DIR"
echo "  Log directory: $LOG_DIR"

# Ensure data/snapshots/ directory exists (needed by SyncService)
mkdir -p "$APP_ROOT/data/snapshots"
chmod 755 "$APP_ROOT/data/snapshots"
echo "  Snapshots directory: $APP_ROOT/data/snapshots"

# ─────────────────────────────────────────────────────────────────────────────
# 3. Verify sync script runs
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "=== Verifying sync script syntax ==="
"$PHP_BIN" -l "$SYNC_SCRIPT"

echo ""
echo "=== Running sync script manually (first run) ==="
echo "    Output will appear in: $LOG_FILE"
"$PHP_BIN" "$SYNC_SCRIPT" >> "$LOG_FILE" 2>&1
EXIT_CODE=$?

if [[ $EXIT_CODE -eq 0 ]]; then
    echo "  Sync completed successfully (exit 0)"
    echo "  Last log line:"
    tail -n 1 "$LOG_FILE" | sed 's/^/    /'
elif [[ $EXIT_CODE -eq 1 ]]; then
    echo "  Sync exited with code 1 (GitHub API error or rate limit — check $LOG_FILE)"
else
    echo "  Sync exited with code $EXIT_CODE — check $LOG_FILE for details"
fi

# ─────────────────────────────────────────────────────────────────────────────
# 4. Print the cron command to copy into cPanel
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "======================================================================"
echo " CRON COMMAND — copy and paste this into cPanel > Cron Jobs"
echo "======================================================================"
echo ""
echo "  Interval:  */15 * * * *"
echo ""
echo "  Command:"
echo "  $PHP_BIN $SYNC_SCRIPT >> $LOG_FILE 2>&1"
echo ""
echo "======================================================================"
echo ""
echo "cPanel Cron Jobs setup steps:"
echo "  1. Log in to cPanel"
echo "  2. Go to Advanced > Cron Jobs"
echo "  3. Under 'Add New Cron Job', set:"
echo "       Common Settings: Once Per 15 Minutes (*/15 * * * *)"
echo "       Command: $PHP_BIN $SYNC_SCRIPT >> $LOG_FILE 2>&1"
echo "  4. Click 'Add New Cron Job'"
echo ""
echo "To tail the log in real time:"
echo "  tail -f $LOG_FILE"
echo ""
