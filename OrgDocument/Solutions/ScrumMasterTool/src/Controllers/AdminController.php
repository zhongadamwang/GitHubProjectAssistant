<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AdminController — admin-only user management endpoints.
 *
 * Routes (config/routes.php — admin group, AdminMiddleware + AuthMiddleware):
 *   GET  /api/admin/users   → listUsers()
 *   POST /api/admin/users   → createUser()
 *
 * Security: password_hash is NEVER included in any response.
 * Both endpoints require admin role (enforced by AdminMiddleware — returns 403).
 * Unauthenticated requests return 401 (enforced by AuthMiddleware).
 *
 * Source Requirements: ADR-7
 */
final class AdminController
{
    /** Valid role values (must match users.role ENUM). */
    private const VALID_ROLES = ['admin', 'member'];

    public function __construct(private readonly UserRepository $userRepo)
    {
    }

    /**
     * GET /api/admin/users
     *
     * Response 200:
     * { "users": [ { id, email, display_name, role, github_username, created_at }, ... ] }
     */
    public function listUsers(Request $request, Response $response): Response
    {
        try {
            $users = $this->userRepo->findAll();
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        $response->getBody()->write(
            json_encode(['users' => $users], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }

    /**
     * POST /api/admin/users
     *
     * Body (JSON): { email, display_name, password, role, github_username? }
     *
     * Response 201: { "user": { id, email, display_name, role, github_username, created_at } }
     * Response 409: { "error": "Email already in use." }          — duplicate email
     * Response 422: { "errors": { field: "message", ... } }       — validation failure
     */
    public function createUser(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        $errors = $this->validate($body);
        if (!empty($errors)) {
            $response->getBody()->write(
                json_encode(['errors' => $errors], JSON_THROW_ON_ERROR)
            );
            return $response->withStatus(422);
        }

        $email          = trim((string) $body['email']);
        $displayName    = trim((string) $body['display_name']);
        $password       = (string) $body['password'];
        $role           = (string) $body['role'];
        $githubUsername = isset($body['github_username']) && (string) $body['github_username'] !== ''
            ? trim((string) $body['github_username'])
            : null;

        try {
            $user = $this->userRepo->create($email, $password, $displayName, $role, $githubUsername);
        } catch (\PDOException $e) {
            // MySQL duplicate-entry error: SQLSTATE 23000 / error code 1062
            if (($e->errorInfo[1] ?? null) === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                $response->getBody()->write(
                    json_encode(['error' => 'Email already in use.'], JSON_THROW_ON_ERROR)
                );
                return $response->withStatus(409);
            }

            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        $payload = [
            'id'             => $user->id,
            'email'          => $user->email,
            'display_name'   => $user->displayName,
            'role'           => $user->role,
            'github_username'=> $user->githubUsername,
            'created_at'     => $user->createdAt,
        ];

        $response->getBody()->write(
            json_encode(['user' => $payload], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(201);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate incoming user creation fields.
     *
     * @param array<string,mixed> $body
     * @return array<string,string>  Field → error message map; empty on success
     */
    private function validate(array $body): array
    {
        $errors = [];

        // email
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address.';
        }

        // display_name
        $displayName = isset($body['display_name']) ? trim((string) $body['display_name']) : '';
        if ($displayName === '') {
            $errors['display_name'] = 'Display name is required.';
        }

        // password
        $password = isset($body['password']) ? (string) $body['password'] : '';
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        // role
        $role = isset($body['role']) ? (string) $body['role'] : '';
        if (!in_array($role, self::VALID_ROLES, true)) {
            $errors['role'] = 'Role must be one of: ' . implode(', ', self::VALID_ROLES) . '.';
        }

        return $errors;
    }
}

