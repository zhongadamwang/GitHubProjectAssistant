<?php
declare(strict_types=1);

/**
 * seed.php — Idempotent database seed script for ScrumMasterTool.
 *
 * Creates the initial admin user if one does not already exist.
 * Credentials are read from the .env file and can be overridden via CLI args.
 *
 * Usage:
 *   php database/seed.php
 *   php database/seed.php --email=admin@example.com --password=secret
 *   php database/seed.php --email=admin@example.com --password=secret --name="Admin User"
 *
 * Environment variables (fallback when no CLI args supplied):
 *   ADMIN_EMAIL     — default admin email       (required if not passed via CLI)
 *   ADMIN_PASSWORD  — default admin password     (required if not passed via CLI)
 *   ADMIN_NAME      — default admin display name (optional, defaults to "Admin")
 */

// ---------------------------------------------------------------------------
// Bootstrap — load .env if available
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
// Parse CLI arguments (--key=value format)
// ---------------------------------------------------------------------------
$cliArgs = [];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (preg_match('/^--([a-zA-Z_][a-zA-Z0-9_-]*)=(.*)$/', $arg, $m)) {
        $cliArgs[$m[1]] = $m[2];
    }
}

// ---------------------------------------------------------------------------
// Resolve admin credentials — CLI args take precedence over .env
// ---------------------------------------------------------------------------
$adminEmail    = $cliArgs['email']    ?? $_ENV['ADMIN_EMAIL']    ?? getenv('ADMIN_EMAIL')    ?: '';
$adminPassword = $cliArgs['password'] ?? $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: '';
$adminName     = $cliArgs['name']     ?? $_ENV['ADMIN_NAME']     ?? getenv('ADMIN_NAME')     ?: 'Admin';

if (empty($adminEmail)) {
    fwrite(STDERR, "ERROR: Admin email not set. Use --email=... or set ADMIN_EMAIL in .env\n");
    exit(1);
}

if (empty($adminPassword)) {
    fwrite(STDERR, "ERROR: Admin password not set. Use --password=... or set ADMIN_PASSWORD in .env\n");
    exit(1);
}

if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "ERROR: ADMIN_EMAIL is not a valid email address.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Database connection
// ---------------------------------------------------------------------------
$host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: '127.0.0.1';
$port     = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '3306';
$dbname   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '';
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: '';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

if (empty($dbname) || empty($username)) {
    fwrite(STDERR, "ERROR: DB_DATABASE and DB_USERNAME must be set in the environment.\n");
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
// Idempotency check — skip if admin user already exists
// ---------------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT id FROM `users` WHERE email = ? LIMIT 1');
$stmt->execute([$adminEmail]);
$existing = $stmt->fetch();

if ($existing !== false) {
    echo "[SKIP] Admin user already exists (email: {$adminEmail}). No changes made.\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Hash password and insert admin user (ADR-7: bcrypt, cost 12)
// ---------------------------------------------------------------------------
$passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);

$insert = $pdo->prepare(
    'INSERT INTO `users` (email, password_hash, display_name, role) VALUES (?, ?, ?, ?)'
);
$insert->execute([$adminEmail, $passwordHash, $adminName, 'admin']);

$newId = (int) $pdo->lastInsertId();

echo "[OK] Admin user created successfully.\n";
echo "     ID:    {$newId}\n";
echo "     Email: {$adminEmail}\n";
echo "     Name:  {$adminName}\n";
echo "     Role:  admin\n";

exit(0);
