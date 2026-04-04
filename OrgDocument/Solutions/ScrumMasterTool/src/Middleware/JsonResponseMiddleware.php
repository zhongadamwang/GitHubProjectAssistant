<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JsonResponseMiddleware — Ensures all API responses carry the correct
 * Content-Type header (`application/json; charset=utf-8`).
 *
 * This middleware runs after routing so it applies only to responses that
 * pass through the Slim pipeline. It is intentionally a thin pass-through
 * that only mutates the Content-Type header.
 */
final class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
