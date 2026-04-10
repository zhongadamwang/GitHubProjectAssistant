<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\IssueRepository;
use App\Services\TimeTrackingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * IssueController — HTTP handlers for issue listing and time-tracking updates.
 *
 * Routes (config/routes.php — auth group):
 *   GET /api/projects/{id}/issues   → getIssues()
 *   PUT /api/issues/{id}/time       → updateTime()
 */
final class IssueController
{
    public function __construct(
        private readonly IssueRepository     $issueRepo,
        private readonly TimeTrackingService $timeTrackingService,
    ) {
    }

    /**
     * GET /api/projects/{id}/issues?assignee=X&iteration=X&status=open|closed
     *
     * Response 200:
     * { "issues": [ {...}, ... ], "total": int }
     *
     * Each issue object includes all columns except github_issue_id internals.
     */
    public function getIssues(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $params    = $request->getQueryParams();

        $filters = [];
        foreach (['assignee', 'iteration', 'status'] as $key) {
            if (isset($params[$key]) && trim((string) $params[$key]) !== '') {
                $filters[$key] = trim((string) $params[$key]);
            }
        }

        try {
            $issues = $this->issueRepo->findByProject($projectId, $filters);
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        // Decode JSON labels column for each row
        $issues = array_map(static function (array $row): array {
            if (isset($row['labels']) && is_string($row['labels'])) {
                $decoded = json_decode($row['labels'], true);
                $row['labels'] = is_array($decoded) ? $decoded : [];
            }
            return $row;
        }, $issues);

        $response->getBody()->write(
            json_encode(
                ['issues' => $issues, 'total' => count($issues)],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            )
        );

        return $response->withStatus(200);
    }

    /**
     * PUT /api/issues/{id}/time
     *
     * Body (JSON): { "estimated_time"?: float, "remaining_time"?: float, "actual_time"?: float }
     *
     * Success 200: updated issue row as JSON
     * Failure 400: { "error": "message" }  — validation failure
     * Failure 404: { "error": "Issue not found" }
     */
    public function updateTime(Request $request, Response $response, array $args): Response
    {
        $issueId   = (int) ($args['id'] ?? 0);
        $authUser  = $request->getAttribute('auth_user');
        $changedBy = $authUser?->id ?? 0;

        $body   = (array) ($request->getParsedBody() ?? []);
        $fields = array_intersect_key($body, array_flip(['estimated_time', 'remaining_time', 'actual_time']));

        try {
            $this->timeTrackingService->updateTime($issueId, $changedBy, $fields);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(
                json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(400);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(
                json_encode(['error' => 'Issue not found.'], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(404);
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        // Return the updated issue row
        $updated = $this->issueRepo->findById($issueId);
        if ($updated === null) {
            // Should never happen, but guard defensively
            $response->getBody()->write(
                json_encode(['error' => 'Issue not found.'], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(404);
        }

        if (isset($updated['labels']) && is_string($updated['labels'])) {
            $decoded = json_decode($updated['labels'], true);
            $updated['labels'] = is_array($decoded) ? $decoded : [];
        }

        $response->getBody()->write(
            json_encode($updated, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }
}
