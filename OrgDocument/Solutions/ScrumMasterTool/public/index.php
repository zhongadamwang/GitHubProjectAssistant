<?php
declare(strict_types=1);

/**
 * index.php — Slim 4 front controller for ScrumMasterTool.
 *
 * Loads environment variables then delegates to the shared app factory
 * (bootstrap/app.php) and calls $app->run().
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// Load .env
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($envPath);
    $dotenv->load();
}

// Serve compiled Vue SPA for all non-API routes so that client-side
// navigation (Vue Router history mode) works when the page is refreshed.
// Skip if the requested path is an actual file (assets, favicon, etc.)
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$requestedFile = __DIR__ . $path;

if (!str_starts_with($path, '/api/') && !is_file($requestedFile)) {
    $distIndex = __DIR__ . '/dist/index.html';
    if (is_file($distIndex)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($distIndex);
        exit;
    }
}

/** @var \Slim\App $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->run();
