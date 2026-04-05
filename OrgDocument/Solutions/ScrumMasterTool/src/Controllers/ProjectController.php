<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ProjectController — project listing and detail endpoints.
 *
 * GET /api/projects       → listProjects()
 * GET /api/projects/{id}  → getProject()
 */
final class ProjectController
{
    public function __construct(
        private readonly ProjectRepository $projectRepo,
        private readonly IssueRepository   $issueRepo,
    ) {
    }

    /**
     * GET /api/projects
     *
     * Returns all projects ordered by last sync timestamp DESC.
     */
    public function index(Request $request, Response $response): Response
    {
        $projects = $this->projectRepo->findAll();
        return $this->json($response, ['projects' => $projects]);
    }

    /**
     * GET /api/projects/{id}
     *
     * Returns project detail with live open/closed issue counts.
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $project = $this->projectRepo->findById($id);

        if ($project === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        $counts = $this->issueRepo->getCountsByProject($id);
        $project['open_count']   = $counts['open'];
        $project['closed_count'] = $counts['closed'];

        return $this->json($response, ['project' => $project]);
    }

    /**
     * GET /api/projects/{id}/issues — delegated to IssueController::getIssues()
     * Kept here to satisfy original route binding; forwards via Slim container.
     */
    public function issues(Request $request, Response $response, array $args): Response
    {
        // Route is now bound directly to IssueController — this stub is
        // superseded once routes.php is updated. Kept for backward compat.
        return $this->json($response, ['error' => 'Use IssueController::getIssues()'], 501);
    }

    /**
     * GET /api/projects/{id}/burndown — handled by BurndownController.
     */
    public function burndown(Request $request, Response $response, array $args): Response
    {
        return $this->json($response, ['error' => 'Use BurndownController::getBurndown()'], 501);
    }

    /**
     * GET /api/projects/{id}/members — handled by MemberController.
     */
    public function members(Request $request, Response $response, array $args): Response
    {
        return $this->json($response, ['error' => 'Use MemberController::getMembers()'], 501);
    }

    // -------------------------------------------------------------------------

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}

