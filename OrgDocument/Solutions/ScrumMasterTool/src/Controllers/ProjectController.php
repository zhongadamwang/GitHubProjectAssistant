<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ProjectController — HTTP handlers for project listing and detail.
 *
 * Routes (config/routes.php — auth group):
 *   GET /api/projects          → listProjects()
 *   GET /api/projects/{id}     → getProject()
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
     * Response 200:
     * { "projects": [ {...}, ... ] }
     */
    public function listProjects(Request $request, Response $response): Response
    {
        try {
            $projects = $this->projectRepo->findAll();
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        $response->getBody()->write(
            json_encode(
                ['projects' => $projects],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            )
        );

        return $response->withStatus(200);
    }

    /**
     * GET /api/projects/{id}
     *
     * Response 200:
     * { "project": {..., "open_count": int, "closed_count": int} }
     * Response 404: { "error": "Project not found" }
     */
    public function getProject(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        try {
            $project = $this->projectRepo->findById($projectId);
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        if ($project === null) {
            $response->getBody()->write(
                json_encode(['error' => 'Project not found.'], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(404);
        }

        try {
            $counts = $this->issueRepo->getCountsByProject($projectId);
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        $payload = array_merge($project, [
            'open_count'   => $counts['open'],
            'closed_count' => $counts['closed'],
        ]);

        $response->getBody()->write(
            json_encode(['project' => $payload], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }

    // ------------------------------------------------------------------
    // Legacy route aliases kept for routes.php compatibility
    // ------------------------------------------------------------------

    /** @deprecated Use listProjects() — kept for routes configured as 'index' */
    public function index(Request $request, Response $response): Response
    {
        return $this->listProjects($request, $response);
    }

    /** @deprecated Use getProject() — kept for routes configured as 'show' */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->getProject($request, $response, $args);
    }

    /** @deprecated Issues are now served by IssueController::getIssues() */
    public function issues(Request $request, Response $response, array $args): Response
    {
        $response->getBody()->write(
            json_encode(['error' => 'Use GET /api/projects/{id}/issues'], JSON_THROW_ON_ERROR)
        );
        return $response->withStatus(308);
    }
}


    /**
     * GET /api/projects/{id}/burndown
     */
    public function burndown(Request $request, Response $response, array $args): Response
    {
        return $this->notImplemented($response);
    }

    /**
     * GET /api/projects/{id}/members
     */
    public function members(Request $request, Response $response, array $args): Response
    {
        return $this->notImplemented($response);
    }

    // -------------------------------------------------------------------------

    private function notImplemented(Response $response): Response
    {
        $response->getBody()->write(
            json_encode(['error' => 'Not implemented. Available in Phase 2.'], JSON_THROW_ON_ERROR)
        );
        return $response->withStatus(501);
    }
}
