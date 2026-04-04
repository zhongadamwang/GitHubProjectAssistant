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
// 1. Load .env.test (falls back to .env if .env.test is absent)
// ---------------------------------------------------------------------------
$root = dirname(__DIR__);

$testEnvFile = $root . '/.env.test';
$devEnvFile  = $root . '/.env';

if (file_exists($testEnvFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable($root, '.env.test');
    $dotenv->load();
} elseif (file_exists($devEnvFile)) {
    echo "[WARN] .env.test not found — falling back to .env. Create .env.test for isolation.\n";
    $dotenv = Dotenv\Dotenv::createImmutable($root);
    $dotenv->load();
} else {
    echo "[WARN] No .env.test or .env found. Tests will use environment variables as-is.\n";
}

// ---------------------------------------------------------------------------
// 2. Configure PHP sessions for CLI
//    session.use_cookies=0 allows PHP sessions to work without HTTP cookies.
//    session.use_only_cookies=0 allows the session ID to be passed in other ways.
// ---------------------------------------------------------------------------
ini_set('session.use_cookies',      '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_strict_mode',  '0');

// ---------------------------------------------------------------------------
// 3. Create test database if it does not exist, then run migrations
// ---------------------------------------------------------------------------
$host   = $_ENV['DB_HOST']    ?? 'localhost';
$port   = $_ENV['DB_PORT']    ?? '3306';
$dbname = $_ENV['DB_NAME']    ?? '';
$user   = $_ENV['DB_USER']    ?? '';
$pass   = $_ENV['DB_PASS']    ?? '';

if (empty($dbname) || empty($user)) {
    echo "[ERROR] DB_NAME and DB_USER must be set in .env.test to run integration tests.\n";
    exit(1);
}

try {
    // Connect without specifying a database to allow CREATE DATABASE
    $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $rootPdo  = new PDO($rootDsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Test database '{$dbname}' ready.\n";
} catch (\PDOException $e) {
    echo "[ERROR] Could not create test database: " . $e->getMessage() . "\n";
    exit(1);
}

// Run migrations via the existing migrate.php script
$migrateScript = __DIR__ . '/../database/migrate.php';
if (file_exists($migrateScript)) {
    // Capture output so PHPUnit startup is clean; print only on error
    ob_start();
    require $migrateScript;
    $migrationOutput = ob_get_clean();
    if (str_contains((string) $migrationOutput, 'ERROR')) {
        echo $migrationOutput;
        exit(1);
    }
    echo "[OK] Migrations applied.\n";
}
