<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * IssueController — placeholder for Phase 2/3 time-tracking implementation.
 *
 * Returns 501 Not Implemented until Phase 3 (T015–T016) delivers
 * TimeTrackingService logic.
 */
final class IssueController
{
    /**
     * PUT /api/issues/{id}/time
     */
    public function updateTime(Request $request, Response $response, array $args): Response
    {
        $response->getBody()->write(
            json_encode(['error' => 'Not implemented. Available in Phase 3.'], JSON_THROW_ON_ERROR)
        );
        return $response->withStatus(501);
    }
}
