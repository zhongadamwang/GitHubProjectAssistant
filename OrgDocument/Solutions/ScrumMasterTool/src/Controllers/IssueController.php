<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\IssueRepository;
use App\Services\TimeTrackingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * IssueController — issue listing and time-tracking endpoints.
 *
 * GET  /api/projects/{id}/issues   → getIssues()
 * PUT  /api/issues/{id}/time       → updateTime()
 */
final class IssueController
{
    public function __construct(
        private readonly IssueRepository    $issueRepo,
        private readonly TimeTrackingService $timeService,
    ) {
    }

    /**
     * GET /api/projects/{id}/issues
     *
     * Optional query params: assignee, iteration, status (open|closed)
     */
    public function getIssues(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $query     = $request->getQueryParams();

        $filters = array_filter([
            'assignee'  => $query['assignee']  ?? '',
            'iteration' => $query['iteration'] ?? '',
            'status'    => $query['status']    ?? '',
        ]);

        $issues = $this->issueRepo->findByProject($projectId, $filters);
        $total  = count($issues);

        return $this->json($response, ['issues' => $issues, 'total' => $total]);
    }

    /**
     * PUT /api/issues/{id}/time
     *
     * Body (JSON): { estimated_time?, remaining_time?, actual_time? }
     * Requires authenticated session (AuthMiddleware).
     */
    public function updateTime(Request $request, Response $response, array $args): Response
    {
        $issueId   = (int) ($args['id'] ?? 0);
        $authUser  = $request->getAttribute('auth_user');
        $changedBy = $authUser?->id ?? 0;

        $body = (array) ($request->getParsedBody() ?? []);

        try {
            $this->timeService->updateTime($issueId, $changedBy, $body);
        } catch (\InvalidArgumentException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 404);
        }

        $updated = $this->issueRepo->findById($issueId);
        return $this->json($response, ['issue' => $updated]);
    }

    // -------------------------------------------------------------------------

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}

