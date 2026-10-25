<?php

declare(strict_types=1);

/**
 * cron/sync.php — GitHub Projects v2 sync entry point for cPanel cron.
 *
 * Typical cPanel cron command:
 *   php /home/<user>/public_html/cron/sync.php >> /home/<user>/logs/sync.log 2>&1
 *
 * Behaviour:
 *  1. Acquire a PID-based file lock (data/sync.lock) — exits 0 if already running
 *  2. Bootstrap the DI container (loads .env, autoloader)
 *  3. Resolve SyncOrchestrator and sync every tracked project (sequentially; one project's
 *     failure does not stop the others)
 *  4. Print one timestamped log line per project
 *  5. Release the lock unconditionally (register_shutdown_function)
 *
 * Exit codes:
 *   0 — all projects synced (or nothing to do / concurrent-lock-skip)
 *   1 — at least one project failed, was rate limited or needs admin attention
 *   2 — unexpected exception
 */

use App\Services\SyncOrchestrator;

$rootDir  = dirname(__DIR__);
$lockFile = $rootDir . '/data/sync.lock';

// ---------------------------------------------------------------------------
// Autoloader + .env
// ---------------------------------------------------------------------------
require $rootDir . '/vendor/autoload.php';

if (file_exists($rootDir . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
    $dotenv->load();
}

// ---------------------------------------------------------------------------
// Lock-file guard — prevent concurrent cron runs
// ---------------------------------------------------------------------------
$lockAcquired = false;

if (file_exists($lockFile)) {
    $existingPid = (int) trim((string) file_get_contents($lockFile));

    if ($existingPid > 0 && function_exists('posix_kill') && posix_kill($existingPid, 0)) {
        // Process is still alive — skip this run gracefully
        $ts = gmdate('Y-m-d H:i:s');
        fwrite(STDOUT, "[{$ts} UTC] Sync skipped: already running (PID {$existingPid})\n");
        exit(0);
    }

    // Stale lock from a previously crashed run — remove it
    @unlink($lockFile);
}

// Write our PID to the lock file
file_put_contents($lockFile, (string) getmypid(), LOCK_EX);
$lockAcquired = true;

// Register unconditional cleanup so the lock is always removed
register_shutdown_function(static function () use ($lockFile, &$lockAcquired): void {
    if ($lockAcquired && file_exists($lockFile)) {
        @unlink($lockFile);
    }
});

// ---------------------------------------------------------------------------
// Bootstrap container
// ---------------------------------------------------------------------------
/** @var \Psr\Container\ContainerInterface $container */
$container = require $rootDir . '/config/container.php';

// ---------------------------------------------------------------------------
// Run sync
// ---------------------------------------------------------------------------
$ts = gmdate('Y-m-d H:i:s');

try {
    /** @var SyncOrchestrator $orchestrator */
    $orchestrator = $container->get(SyncOrchestrator::class);
    $outcomes     = $orchestrator->runAll();

    if ($outcomes === []) {
        fwrite(STDOUT, '[' . gmdate('Y-m-d H:i:s') . " UTC] Sync skipped: no tracked projects (configure a GitHub account and track a project)\n");
        exit(0);
    }

    $exit = 0;

    foreach ($outcomes as $outcome) {
        $ts   = gmdate('Y-m-d H:i:s');
        $line = "[{$ts} UTC] Project \"{$outcome->name}\": {$outcome->status}";

        if ($outcome->result !== null) {
            $line .= ": {$outcome->result->added} added, {$outcome->result->updated} updated, {$outcome->result->unchanged} unchanged"
                . ($outcome->result->errors > 0 ? ", {$outcome->result->errors} errors" : '');
        }
        if ($outcome->message !== '') {
            $line .= " ({$outcome->message})";
        }
        $line .= "\n";

        if ($outcome->isSuccess()) {
            fwrite(STDOUT, $line);
        } else {
            fwrite(STDERR, $line);
            logSyncError($rootDir, $line);
            $exit = 1;
        }
    }

    exit($exit);

} catch (\Throwable $e) {
    $ts  = gmdate('Y-m-d H:i:s');
    $msg = "[{$ts} UTC] Sync failed (unexpected): " . get_class($e) . ": {$e->getMessage()}\n";
    fwrite(STDERR, $msg);
    logSyncError($rootDir, $msg);
    exit(2);
}

/**
 * Append an error message to logs/sync-error.log.
 * Creates the logs/ directory if it does not exist.
 */
function logSyncError(string $rootDir, string $message): void
{
    $logsDir  = $rootDir . '/logs';
    $logFile  = $logsDir . '/sync-error.log';

    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }

    @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
}
