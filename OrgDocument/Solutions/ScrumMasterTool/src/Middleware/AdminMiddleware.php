<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AdminMiddleware — rejects non-admin users with 403.
 *
 * Must run after AuthMiddleware (which populates the 'auth_user' request
 * attribute). If auth_user is absent, returns 401 (AuthMiddleware should have
 * caught this first, but we guard defensively).
 *
 * Apply to route groups that require the 'admin' role:
 *   /api/admin/*
 *   POST /api/sync/trigger
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var User|null $user */
        $user = $request->getAttribute('auth_user');

        if ($user === null) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(
                json_encode(['error' => 'Authentication required.'], JSON_THROW_ON_ERROR)
            );
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        if (!$user->isAdmin()) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write(
                json_encode(['error' => 'Admin access required.'], JSON_THROW_ON_ERROR)
            );
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $handler->handle($request);
    }
}
