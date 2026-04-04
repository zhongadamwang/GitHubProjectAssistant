<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AuthController — HTTP handlers for authentication endpoints.
 *
 * Routes (registered in config/routes.php):
 *   POST /api/auth/login   → login()
 *   POST /api/auth/logout  → logout()
 *   GET  /api/auth/me      → me()
 *
 * All responses are JSON (Content-Type set by JsonResponseMiddleware).
 */
final class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * POST /api/auth/login
     *
     * Body (JSON): { "email": "...", "password": "..." }
     *
     * Success 200: { "user": { id, email, display_name, role, github_username } }
     * Failure 400: { "error": "..." }     — missing / malformed input
     * Failure 401: { "error": "..." }     — invalid credentials (generic)
     */
    public function login(Request $request, Response $response): Response
    {
        $body     = (array) ($request->getParsedBody() ?? []);
        $email    = isset($body['email'])    ? trim((string) $body['email'])    : '';
        $password = isset($body['password']) ? (string) $body['password']       : '';

        if ($email === '' || $password === '') {
            return $this->json($response, ['error' => 'Email and password are required.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => 'Invalid email format.'], 400);
        }

        try {
            $user = $this->authService->login($email, $password);
        } catch (\InvalidArgumentException) {
            // Generic message — no user enumeration
            return $this->json($response, ['error' => 'Invalid credentials.'], 401);
        }

        return $this->json($response, ['user' => $user->toApiArray()]);
    }

    /**
     * POST /api/auth/logout
     *
     * Success 200: { "message": "Logged out." }
     */
    public function logout(Request $request, Response $response): Response
    {
        $this->authService->logout();

        return $this->json($response, ['message' => 'Logged out.']);
    }

    /**
     * GET /api/auth/me
     *
     * Success 200: { "user": { ... } }
     * Failure 401: { "error": "Not authenticated." }
     *
     * This endpoint is also protected by AuthMiddleware, so a 401 from here
     * is a secondary guard (AuthMiddleware fires first on protected routes).
     */
    public function me(Request $request, Response $response): Response
    {
        $user = $this->authService->getCurrentUser();

        if ($user === null) {
            return $this->json($response, ['error' => 'Not authenticated.'], 401);
        }

        return $this->json($response, ['user' => $user->toApiArray()]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}
