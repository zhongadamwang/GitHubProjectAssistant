<?php

declare(strict_types=1);

/**
 * cron/import-env-account.php — one-time upgrade step: move the legacy .env GitHub settings
 * (REPO_ORG, REPO_PAT, REPO_PROJECT_NUMBER, REPO_GRAPHQL_URL) into an encrypted account and a
 * tracked project. Safe to run repeatedly.
 *
 *   php cron/import-env-account.php
 *
 * Requires APP_KEY (php cron/generate-app-key.php) and migrations 011/012 to be applied.
 * Afterwards REPO_PAT / REPO_ORG / REPO_PROJECT_NUMBER can be removed from .env.
 *
 * Exit codes: 0 imported or already imported, 1 legacy settings missing or APP_KEY missing, 2 unexpected error.
 */

use App\Services\EnvAccountImporter;

$rootDir = dirname(__DIR__);

require $rootDir . '/vendor/autoload.php';

if (file_exists($rootDir . '/.env')) {
    Dotenv\Dotenv::createImmutable($rootDir)->load();
}

try {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require $rootDir . '/config/container.php';

    $result = $container->get(EnvAccountImporter::class)->import();

    foreach ($result['messages'] as $message) {
        fwrite(STDOUT, $message . "\n");
    }
    fwrite(STDOUT, 'Result: ' . $result['status'] . "\n");

    if ($result['status'] === 'imported') {
        fwrite(STDOUT, "Next: open Admin → GitHub accounts, test the connection, then you can remove REPO_PAT from .env.\n");
    }

    exit(in_array($result['status'], ['imported', 'already_imported'], true) ? 0 : 1);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Import failed: ' . get_class($e) . "\n");
    exit(2);
}
