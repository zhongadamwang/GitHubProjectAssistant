<?php
declare(strict_types=1);

/**
 * migrate.php — Idempotent migration runner for ScrumMasterTool.
 *
 * Usage:
 *   php database/migrate.php
 *
 * Reads all *.sql files from database/migrations/ in alphabetical order and
 * executes any that have not yet been recorded in the `migrations_log` table.
 *
 * Environment: expects DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 * to be set (loaded from ../.env via vlucas/phpdotenv when available).
 */

// ---------------------------------------------------------------------------
// Bootstrap — load .env if not already set
// ---------------------------------------------------------------------------
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} elseif (file_exists($envFile)) {
    // Minimal inline parser for CLI use without autoloader
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

// ---------------------------------------------------------------------------
// Database connection
// ---------------------------------------------------------------------------
$host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: '127.0.0.1';
$port     = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '3306';
$dbname   = $_ENV['DB_NAME']     ?? getenv('DB_NAME')     ?: '';
$username = $_ENV['DB_USER']     ?? getenv('DB_USER')     ?: '';
$password = $_ENV['DB_PASS']     ?? getenv('DB_PASS')     ?: '';

if (empty($dbname) || empty($username)) {
    fwrite(STDERR, "ERROR: DB_NAME and DB_USER must be set in the environment.\n");
    exit(1);
}

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (\PDOException $e) {
    fwrite(STDERR, 'ERROR: Could not connect to database: ' . $e->getMessage() . "\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Ensure migrations_log table exists
// ---------------------------------------------------------------------------
$pdo->exec(<<<'SQL'
    CREATE TABLE IF NOT EXISTS `migrations_log` (
        `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `migration`   VARCHAR(255) NOT NULL,
        `applied_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_migrations_log_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

// ---------------------------------------------------------------------------
// Collect already-applied migrations
// ---------------------------------------------------------------------------
$applied = $pdo->query('SELECT `migration` FROM `migrations_log`')
               ->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);   // hash-map for O(1) lookup

// ---------------------------------------------------------------------------
// Discover and sort migration files
// ---------------------------------------------------------------------------
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
if ($files === false || count($files) === 0) {
    echo "No migration files found in {$migrationsDir}.\n";
    exit(0);
}
sort($files);   // alphabetical → numerical order (001, 002, …)

// ---------------------------------------------------------------------------
// Apply pending migrations
// ---------------------------------------------------------------------------
$applied_count = 0;
$skipped_count = 0;

foreach ($files as $filepath) {
    $filename = basename($filepath);

    if (isset($applied[$filename])) {
        echo "  SKIP  {$filename}\n";
        $skipped_count++;
        continue;
    }

    $sql = file_get_contents($filepath);
    if ($sql === false || trim($sql) === '') {
        echo "  WARN  {$filename} — empty or unreadable, skipping\n";
        continue;
    }

    echo "  RUN   {$filename} … ";
    try {
        // DDL statements (CREATE TABLE etc.) cause implicit commits in MySQL,
        // so we cannot wrap them in a transaction. Execute directly.
        // Strip SQL line comments before splitting on semicolons to avoid
        // splitting on semicolons that appear inside comment text.
        $stripped = preg_replace('/--[^\n]*\n/', "\n", $sql);
        foreach (array_filter(array_map('trim', explode(';', (string) $stripped))) as $stmt) {
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }
        // Record as applied
        $insert = $pdo->prepare('INSERT INTO `migrations_log` (`migration`) VALUES (?)');
        $insert->execute([$filename]);
        echo "OK\n";
        $applied_count++;
    } catch (\PDOException $e) {
        $msg = $e->getMessage();
        fwrite(STDERR, "FAILED\nERROR: {$msg}\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\nMigrations complete: {$applied_count} applied, {$skipped_count} skipped.\n";

// Only exit when run as a standalone CLI script, not when required by tests.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    exit(0);
}
