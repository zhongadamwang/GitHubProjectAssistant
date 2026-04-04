<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * SyncController — placeholder for Phase 2 GitHub cron sync endpoints.
 *
 * Returns 501 Not Implemented until Phase 2 (T010–T011) delivers the
 * sync entry point and history repository.
 */
final class SyncController
{
    /**
     * GET /api/sync/history
     */
    public function history(Request $request, Response $response): Response
    {
        return $this->notImplemented($response, 'Phase 2');
    }

    /**
     * POST /api/sync/trigger  (admin only)
     */
    public function trigger(Request $request, Response $response): Response
    {
        return $this->notImplemented($response, 'Phase 2');
    }

    // -------------------------------------------------------------------------

    private function notImplemented(Response $response, string $phase): Response
    {
        $response->getBody()->write(
            json_encode(
                ['error' => "Not implemented. Available in {$phase}."],
                JSON_THROW_ON_ERROR
            )
        );
        return $response->withStatus(501);
    }
}
