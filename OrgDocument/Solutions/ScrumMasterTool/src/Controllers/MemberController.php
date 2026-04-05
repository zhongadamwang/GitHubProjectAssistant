<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EfficiencyService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * MemberController — member efficiency analytics endpoint.
 *
 * GET /api/projects/{id}/members?iteration=X
 */
final class MemberController
{
    public function __construct(private readonly EfficiencyService $efficiencyService)
    {
    }

    /**
     * GET /api/projects/{id}/members
     *
     * Optional query param: iteration (string)
     *
     * Response:
     * {
     *   project_id: int,
     *   iteration:  string|null,
     *   members:    [{member, estimated, actual, ratio, issues_count}],
     *   trend:      { member_login: [{iteration, estimated, actual, ratio, issues_count}] }
     * }
     */
    public function getMembers(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $query     = $request->getQueryParams();

        $iteration = (isset($query['iteration']) && $query['iteration'] !== '')
            ? (string) $query['iteration']
            : null;

        $members = $this->efficiencyService->getMemberEfficiency($projectId, $iteration);

        // Build trend map keyed by member login
        $trend = [];
        foreach ($members as $m) {
            $login          = $m['member'];
            $trend[$login]  = $this->efficiencyService->getMemberTrend($projectId, $login);
        }

        return $this->json($response, [
            'project_id' => $projectId,
            'iteration'  => $iteration,
            'members'    => $members,
            'trend'      => $trend,
        ]);
    }

    // -------------------------------------------------------------------------

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}
