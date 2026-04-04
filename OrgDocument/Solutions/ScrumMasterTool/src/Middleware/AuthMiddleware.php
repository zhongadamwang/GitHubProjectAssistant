<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * AuthMiddleware — rejects unauthenticated requests with 401.
 *
 * Apply to any route group that requires a valid session.
 * On success the authenticated User object is stored as a request attribute
 * under the key 'auth_user' so downstream handlers can access it without
 * an additional session lookup.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthService              $authService,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->authService->getCurrentUser();

        if ($user === null) {
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(
                json_encode(['error' => 'Authentication required.'], JSON_THROW_ON_ERROR)
            );
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        // Attach user to request attributes for use by controllers
        $request = $request->withAttribute('auth_user', $user);

        return $handler->handle($request);
    }
}
