<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EfficiencyService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * MemberController — HTTP handler for member efficiency data.
 *
 * Route (config/routes.php):
 *   GET /api/projects/{id}/members   → getMembers()  [auth required]
 */
final class MemberController
{
    public function __construct(private readonly EfficiencyService $efficiencyService)
    {
    }

    /**
     * GET /api/projects/{id}/members?iteration=X
     *
     * Query params:
     *   iteration  (optional) — sprint/iteration name to scope the aggregation;
     *              omit to aggregate across all iterations
     *
     * Response 200:
     * {
     *   "project_id": 1,
     *   "iteration": "Sprint 1" | null,
     *   "members": [
     *     {
     *       "member": "alice",
     *       "estimated": 20.0,
     *       "actual": 22.5,
     *       "ratio": 1.125,
     *       "issues_count": 4
     *     },
     *     ...
     *   ],
     *   "trend": {
     *     "alice": [
     *       { "iteration": "Sprint 1", "estimated": 20.0, "actual": 22.5, "ratio": 1.125, "issues_count": 4 },
     *       ...
     *     ],
     *     ...
     *   }
     * }
     *
     * Members with no closed issues in scope are omitted from both
     * `members` and `trend`.
     */
    public function getMembers(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $params    = $request->getQueryParams();
        $iteration = isset($params['iteration']) ? trim((string) $params['iteration']) : null;

        if ($iteration === '') {
            $iteration = null;
        }

        try {
            $members = $this->efficiencyService->getMemberEfficiency($projectId, $iteration);

            // Build trend map — one entry per distinct member in the result set
            $trend = [];
            foreach ($members as $record) {
                $login = $record['member'];
                $trend[$login] = $this->efficiencyService->getMemberTrend($projectId, $login);
            }
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        $payload = [
            'project_id' => $projectId,
            'iteration'  => $iteration,
            'members'    => $members,
            'trend'      => $trend,
        ];

        $response->getBody()->write(
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }
}
