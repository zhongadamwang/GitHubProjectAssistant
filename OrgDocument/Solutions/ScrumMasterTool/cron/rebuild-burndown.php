<?php

declare(strict_types=1);

/**
 * cron/rebuild-burndown.php — repair burndown snapshots after hours became available.
 *
 * Run once after deploying the hours sync and completing a real sync:
 *   php cron/rebuild-burndown.php [--project=ID]
 *
 * For each project (or the one given):
 *  1. Delete snapshots with total_estimated = 0 (captured while hours were not synced)
 *  2. Capture today's snapshot from the current issue data
 *
 * Exit codes: 0 success, 2 unexpected error.
 */

use App\Repositories\BurndownRepository;
use App\Repositories\ProjectRepository;
use App\Services\BurndownService;

$rootDir = dirname(__DIR__);

require $rootDir . '/vendor/autoload.php';

if (file_exists($rootDir . '/.env')) {
    Dotenv\Dotenv::createImmutable($rootDir)->load();
}

/** @var \Psr\Container\ContainerInterface $container */
$container = require $rootDir . '/config/container.php';

$onlyProject = null;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--project=(\d+)$/', $arg, $m)) {
        $onlyProject = (int) $m[1];
    }
}

try {
    $projectRepo  = $container->get(ProjectRepository::class);
    $burndownRepo = $container->get(BurndownRepository::class);
    $burndown     = $container->get(BurndownService::class);

    foreach ($projectRepo->findAll() as $project) {
        $projectId = (int) $project['id'];

        if ($onlyProject !== null && $projectId !== $onlyProject) {
            continue;
        }

        $deleted = $burndownRepo->deleteEmptySnapshots($projectId);
        $burndown->captureDaily($projectId);

        fwrite(STDOUT, sprintf(
            "[%s UTC] project %d (%s): removed %d empty snapshot(s), captured today's snapshot\n",
            gmdate('Y-m-d H:i:s'),
            $projectId,
            $project['name'] ?? '',
            $deleted,
        ));
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'rebuild-burndown failed: ' . $e->getMessage() . "\n");
    exit(2);
}
