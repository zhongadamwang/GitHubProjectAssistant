<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ProjectController — placeholder for Phase 2 GitHub sync implementation.
 *
 * All methods return 501 Not Implemented until Phase 2 (T008–T010) delivers
 * the GitHubGraphQLService and the sync/diff logic.
 */
final class ProjectController
{
    /**
     * GET /api/projects
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->notImplemented($response);
    }

    /**
     * GET /api/projects/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->notImplemented($response);
    }

    /**
     * GET /api/projects/{id}/issues
     */
    public function issues(Request $request, Response $response, array $args): Response
    {
        return $this->notImplemented($response);
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
