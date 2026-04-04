<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BurndownService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * BurndownController — HTTP handler for burndown chart data.
 *
 * Route (config/routes.php):
 *   GET /api/projects/{id}/burndown   → getBurndown()  [auth required]
 */
final class BurndownController
{
    public function __construct(private readonly BurndownService $burndownService)
    {
    }

    /**
     * GET /api/projects/{id}/burndown?iteration=X
     *
     * Query params:
     *   iteration  (optional) — sprint/iteration name; omit to use the most recent
     *
     * Response 200:
     * {
     *   "project_id": 1,
     *   "iteration": "Sprint 1",
     *   "points": [
     *     { "date": "2026-04-01", "ideal": 40.0, "actual": 38.5 },
     *     ...
     *   ]
     * }
     *
     * When no burndown data exists yet, returns:
     * { "project_id": 1, "iteration": "", "points": [] }
     */
    public function getBurndown(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $params    = $request->getQueryParams();
        $iteration = isset($params['iteration']) ? trim((string) $params['iteration']) : null;

        // Treat empty string as no preference (auto-resolve in service)
        if ($iteration === '') {
            $iteration = null;
        }

        $result = $this->burndownService->getBurndown($projectId, $iteration);

        $payload = [
            'project_id' => $projectId,
            'iteration'  => $result['iteration'],
            'points'     => array_map(
                static fn($p) => [
                    'date'   => $p->date,
                    'ideal'  => $p->ideal,
                    'actual' => $p->actual,
                ],
                $result['points'],
            ),
        ];

        $response->getBody()->write(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }
}
