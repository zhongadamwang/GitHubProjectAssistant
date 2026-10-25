<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * RequireJsonMiddleware — CSRF hardening for state-changing admin endpoints.
 *
 * A request that carries a body must declare `Content-Type: application/json`. HTML forms cannot
 * send that content type cross-site without a CORS preflight, so this complements the
 * SameSite=Strict session cookie. Requests without a body (e.g. POST …/sync) pass through.
 */
final class RequireJsonMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $stateChanging = in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        // php://input reports no size, so a real HTTP body is detected from the headers as well
        $hasBody = ($request->getBody()->getSize() ?? 0) > 0
            || (int) $request->getHeaderLine('Content-Length') > 0
            || $request->hasHeader('Transfer-Encoding');

        if ($stateChanging && $hasBody && stripos($request->getHeaderLine('Content-Type'), 'application/json') !== 0) {
            $response = $this->responseFactory->createResponse(415);
            $response->getBody()->write(json_encode(['error' => 'Content-Type must be application/json.'], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $handler->handle($request);
    }
}
