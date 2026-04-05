<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\SyncHistoryRepository;
use App\Services\SyncService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * SyncController — HTTP handlers for GitHub sync endpoints.
 *
 * Routes (config/routes.php):
 *   GET  /api/sync/history       → history()  [auth required]
 *   POST /api/sync/trigger       → trigger()  [admin required]
 */
final class SyncController
{
    public function __construct(
        private readonly SyncService            $syncService,
        private readonly SyncHistoryRepository  $historyRepo,
    ) {
    }

    /**
     * GET /api/sync/history
     *
     * Returns the last 20 sync history records for the configured project,
     * sorted newest first.
     *
     * Response 200:
     * {
     *   "data": [ { "id": 1, "synced_at": "...", "status": "success", ... }, ... ]
     * }
     */
    public function history(Request $request, Response $response): Response
    {
        // project_id=0 is a safe fallback when no row exists yet; the repo
        // returns an empty array which is valid JSON.
        $params    = $request->getQueryParams();
        $projectId = (int) ($params['project_id'] ?? 0);

        $records = $this->historyRepo->findLatest($projectId, 20);

        $response->getBody()->write(
            json_encode(['data' => $records], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }

    /**
     * POST /api/sync/trigger
     *
     * Runs a full sync synchronously and returns the result.
     * Restricted to admin users (AdminMiddleware applied in routes.php).
     *
     * Response 200:
     * {
     *   "status": "success",
     *   "issues_added": 3,
     *   "issues_updated": 1,
     *   "unchanged": 10,
     *   "errors": 0,
     *   "snapshot_file": "/absolute/path/to/2026-04-03_14-00.json"
     * }
     *
     * Response 502 on GitHub API failure:
     * { "error": "GitHub API request failed with HTTP 503" }
     */
    public function trigger(Request $request, Response $response): Response
    {
        try {
            $result = $this->syncService->run();

            $response->getBody()->write(
                json_encode($result->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            );

            return $response->withStatus(200);

        } catch (\Throwable $e) {
            $response->getBody()->write(
                json_encode(
                    ['error' => $e->getMessage()],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                )
            );

            return $response->withStatus(502);
        }
    }
}
