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

/** @var \Slim\App $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->run();
