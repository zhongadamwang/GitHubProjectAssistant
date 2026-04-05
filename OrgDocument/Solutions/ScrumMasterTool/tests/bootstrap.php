<?php
declare(strict_types=1);

/**
 * tests/bootstrap.php — PHPUnit bootstrap for ScrumMasterTool integration tests.
 *
 * Executed once before the test suite starts (configured in phpunit.xml).
 *
 * Responsibilities:
 *   1. Load vendor autoloader
 *   2. Load .env.test environment variables
 *   3. Configure PHP session for CLI (no cookies)
 *   4. Create the test database if it does not exist
 *   5. Run all migrations against the test database
 */

require_once __DIR__ . '/../vendor/autoload.php';

// ---------------------------------------------------------------------------
// 0. Configure PHP sessions for CLI.
//    IMPORTANT: All diagnostic output in this bootstrap MUST use fwrite(STDERR)
//    rather than echo/print. Any output to stdout triggers PHP's "headers sent"
//    flag which causes session_start() to FAIL and silently clear $_SESSION,
//    breaking all session-dependent integration tests.
// ---------------------------------------------------------------------------
ini_set('session.use_cookies',      '0');
// session.use_only_cookies disabling is deprecated in PHP 8.4+ — omit it
ini_set('session.use_strict_mode',  '0');

// ---------------------------------------------------------------------------
// 1. Load .env.test (falls back to .env if .env.test is absent)
// ---------------------------------------------------------------------------
$root = dirname(__DIR__);

$testEnvFile = $root . '/.env.test';
$devEnvFile  = $root . '/.env';

if (file_exists($testEnvFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable($root, '.env.test');
    $dotenv->load();
} elseif (file_exists($devEnvFile)) {
    fwrite(STDERR, "[WARN] .env.test not found — falling back to .env. Create .env.test for isolation.\n");
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->load();
} else {
    fwrite(STDERR, "[WARN] No .env.test or .env found. Tests will use environment variables as-is.\n");
}

// ---------------------------------------------------------------------------
// 2. Create test database if it does not exist, then run migrations
// ---------------------------------------------------------------------------
$host   = $_ENV['DB_HOST']    ?? 'localhost';
$port   = $_ENV['DB_PORT']    ?? '3306';
$dbname = $_ENV['DB_NAME']    ?? '';
$user   = $_ENV['DB_USER']    ?? '';
$pass   = $_ENV['DB_PASS']    ?? '';

if (empty($dbname) || empty($user)) {
    fwrite(STDERR, "[ERROR] DB_NAME and DB_USER must be set in .env.test to run integration tests.\n");
    exit(1);
}

try {
    // Connect without specifying a database to allow CREATE DATABASE
    $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $rootPdo  = new PDO($rootDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    fwrite(STDERR, "[OK] Test database '{$dbname}' ready.\n");
} catch (\PDOException $e) {
    fwrite(STDERR, "[ERROR] Could not create test database: " . $e->getMessage() . "\n");
    exit(1);
}

// Run migrations via the existing migrate.php script
$migrateScript = __DIR__ . '/../database/migrate.php';
if (file_exists($migrateScript)) {
    // Capture stdout from migrate.php so it does not go to PHP's stdout
    // (which would trigger "headers sent" and break sessions).
    ob_start();
    require $migrateScript;
    $migrationOutput = ob_get_clean();
    if (str_contains((string) $migrationOutput, 'ERROR')) {
        fwrite(STDERR, $migrationOutput);
        exit(1);
    }
    fwrite(STDERR, "[OK] Migrations applied.\n");
}
