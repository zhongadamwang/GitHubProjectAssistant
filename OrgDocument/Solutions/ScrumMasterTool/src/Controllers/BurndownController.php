<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BurndownRepository;
use App\Services\BurndownService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * BurndownController — burndown chart data endpoint.
 *
 * GET /api/projects/{id}/burndown?iteration=X
 */
final class BurndownController
{
    public function __construct(
        private readonly BurndownService    $burndownService,
        private readonly BurndownRepository $burndownRepo,
    ) {
    }

    /**
     * GET /api/projects/{id}/burndown
     *
     * Optional query param: iteration (string)
     * When omitted, uses the most recent iteration found in burndown_daily.
     *
     * Response: { project_id: int, iteration: string, points: [{date, ideal, actual}] }
     */
    public function getBurndown(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $query     = $request->getQueryParams();

        $iteration = (isset($query['iteration']) && $query['iteration'] !== '')
            ? (string) $query['iteration']
            : $this->burndownRepo->getLatestIteration($projectId);

        if ($iteration === null) {
            return $this->json($response, [
                'project_id' => $projectId,
                'iteration'  => null,
                'points'     => [],
            ]);
        }

        $points = $this->burndownService->getBurndown($projectId, $iteration);

        return $this->json($response, [
            'project_id' => $projectId,
            'iteration'  => $iteration,
            'points'     => array_map(fn($p) => $p->toArray(), $points),
        ]);
    }

    // -------------------------------------------------------------------------

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}
