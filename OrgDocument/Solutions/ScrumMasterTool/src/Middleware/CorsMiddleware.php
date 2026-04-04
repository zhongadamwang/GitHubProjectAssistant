<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CorsMiddleware — Handles CORS for the Scrum Dashboard API.
 *
 * For session-based auth with cookies, `Access-Control-Allow-Credentials: true`
 * is required and `Access-Control-Allow-Origin` must be a specific origin (not *).
 *
 * Behaviour:
 *  - In development (APP_ENV=development), the request's Origin header is
 *    reflected back so any local dev server is accepted.
 *  - In production, only origins that match APP_CORS_ORIGIN (env var) are
 *    accepted; if APP_CORS_ORIGIN is not set, CORS headers are omitted on
 *    cross-origin requests (same-host SPA needs no CORS).
 *  - OPTIONS preflight requests are answered immediately with 204.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    private const ALLOWED_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';
    private const ALLOWED_HEADERS = 'Content-Type, Authorization, X-Requested-With';
    private const MAX_AGE         = '3600';

    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        // No Origin header → same-origin or non-browser request, skip CORS
        if ($origin === '') {
            return $handler->handle($request);
        }

        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        // OPTIONS preflight — respond immediately without invoking the handler
        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(204);
            return $this->addCorsHeaders($response, $allowedOrigin);
        }

        $response = $handler->handle($request);
        return $this->addCorsHeaders($response, $allowedOrigin);
    }

    /**
     * Determine the value to echo back in Access-Control-Allow-Origin.
     * Returns an empty string when the origin is not permitted.
     */
    private function resolveAllowedOrigin(string $requestOrigin): string
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

        if ($env === 'development') {
            // Reflect any origin in development to allow Vite / local API clients
            return $requestOrigin;
        }

        // Production: only allow the explicitly configured origin
        $configured = $_ENV['APP_CORS_ORIGIN'] ?? getenv('APP_CORS_ORIGIN') ?: '';

        if ($configured === '') {
            // No CORS origin configured → same-host SPA, no cross-origin needed
            return '';
        }

        // Exact match only — no wildcard patterns
        return $requestOrigin === $configured ? $requestOrigin : '';
    }

    private function addCorsHeaders(ResponseInterface $response, string $allowedOrigin): ResponseInterface
    {
        if ($allowedOrigin === '') {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
            ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
            ->withHeader('Access-Control-Max-Age', self::MAX_AGE)
            ->withHeader('Vary', 'Origin');
    }
}
