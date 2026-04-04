<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AdminController — placeholder for Phase 3 user-management endpoints.
 *
 * Returns 501 Not Implemented until Phase 3 (T017) delivers full user
 * management logic.
 */
final class AdminController
{
    /**
     * GET /api/admin/users
     */
    public function listUsers(Request $request, Response $response): Response
    {
        return $this->notImplemented($response);
    }

    /**
     * POST /api/admin/users
     */
    public function createUser(Request $request, Response $response): Response
    {
        return $this->notImplemented($response);
    }

    // -------------------------------------------------------------------------

    private function notImplemented(Response $response): Response
    {
        $response->getBody()->write(
            json_encode(['error' => 'Not implemented. Available in Phase 3.'], JSON_THROW_ON_ERROR)
        );
        return $response->withStatus(501);
    }
}
