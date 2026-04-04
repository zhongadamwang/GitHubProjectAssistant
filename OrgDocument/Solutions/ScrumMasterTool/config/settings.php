<?php
declare(strict_types=1);

/**
 * settings.php — Application-wide settings resolved from environment variables.
 *
 * Returns a structured array consumed by the DI container and middleware.
 * All values originate from .env (loaded in public/index.php before this file
 * is required). Fallbacks cover clean-install and CI environments.
 */
return [

    // -------------------------------------------------------------------------
    // Application
    // -------------------------------------------------------------------------
    'app' => [
        'env'       => $_ENV['APP_ENV']       ?? 'production',
        'debug'     => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        // Trailing slash stripped; empty string = installed at document root
        'base_path' => rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'),
    ],

    // -------------------------------------------------------------------------
    // Database (MySQL via PDO)
    // -------------------------------------------------------------------------
    'db' => [
        'host'    => $_ENV['DB_HOST']    ?? '127.0.0.1',
        'port'    => $_ENV['DB_PORT']    ?? '3306',
        'name'    => $_ENV['DB_NAME']    ?? '',
        'user'    => $_ENV['DB_USER']    ?? '',
        'pass'    => $_ENV['DB_PASS']    ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],

    // -------------------------------------------------------------------------
    // GitHub GraphQL API
    // -------------------------------------------------------------------------
    'github' => [
        'pat'         => $_ENV['GITHUB_PAT']         ?? '',
        'org'         => $_ENV['GITHUB_ORG']         ?? '',
        'graphql_url' => $_ENV['GITHUB_GRAPHQL_URL'] ?? 'https://api.github.com/graphql',
    ],

    // -------------------------------------------------------------------------
    // PHP Sessions (ADR-7)
    // -------------------------------------------------------------------------
    'session' => [
        'name'      => $_ENV['SESSION_NAME']      ?? 'scrum_session',
        'secure'    => filter_var($_ENV['SESSION_SECURE']    ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'http_only' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? 'true',  FILTER_VALIDATE_BOOLEAN),
        'same_site' => $_ENV['SESSION_SAME_SITE'] ?? 'Strict',
        'lifetime'  => (int) ($_ENV['SESSION_LIFETIME'] ?? 28800),
    ],

    // -------------------------------------------------------------------------
    // GitHub sync / snapshots
    // -------------------------------------------------------------------------
    'sync' => [
        'interval_minutes'   => (int) ($_ENV['SYNC_INTERVAL_MINUTES']    ?? 15),
        'snapshot_retention' => (int) ($_ENV['SNAPSHOT_RETENTION_COUNT'] ?? 200),
        'snapshot_dir'       => dirname(__DIR__) . '/data/snapshots',
    ],

];
