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
 *  3. Resolve SyncService and call run()
 *  4. Print a single timestamped log line to stdout
 *  5. Release the lock unconditionally (register_shutdown_function)
 *
 * Exit codes:
 *   0 — success or concurrent-lock-skip
 *   1 — GitHubApiException or RateLimitException
 *   2 — unexpected exception
 */

use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\Services\SyncService;

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
    /** @var SyncService $syncService */
    $syncService = $container->get(SyncService::class);
    $result      = $syncService->run();

    $ts = gmdate('Y-m-d H:i:s');
    fwrite(
        STDOUT,
        "[{$ts} UTC] Sync complete: "
        . "{$result->added} added, "
        . "{$result->updated} updated, "
        . "{$result->unchanged} unchanged"
        . ($result->errors > 0 ? ", {$result->errors} errors" : '')
        . "\n"
    );

    exit(0);

} catch (RateLimitException $e) {
    $ts = gmdate('Y-m-d H:i:s');
    fwrite(STDERR, "[{$ts} UTC] Sync aborted (rate limit): {$e->getMessage()}\n");
    exit(1);

} catch (GitHubApiException $e) {
    $ts = gmdate('Y-m-d H:i:s');
    fwrite(STDERR, "[{$ts} UTC] Sync failed (GitHub API error): {$e->getMessage()}\n");
    exit(1);

} catch (\Throwable $e) {
    $ts = gmdate('Y-m-d H:i:s');
    fwrite(STDERR, "[{$ts} UTC] Sync failed (unexpected): " . get_class($e) . ": {$e->getMessage()}\n");
    exit(2);
}
