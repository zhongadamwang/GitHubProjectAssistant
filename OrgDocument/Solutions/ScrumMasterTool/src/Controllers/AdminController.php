<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * AdminController — admin-only user management endpoints.
 *
 * GET  /api/admin/users  → listUsers()
 * POST /api/admin/users  → createUser()
 *
 * Both routes are protected by AdminMiddleware (requires admin role).
 * password_hash is NEVER included in any response.
 */
final class AdminController
{
    public function __construct(private readonly UserRepository $userRepo)
    {
    }

    /**
     * GET /api/admin/users
     *
     * Returns JSON array of user objects (no password_hash).
     */
    public function listUsers(Request $request, Response $response): Response
    {
        $users = $this->userRepo->findAll();
        return $this->json($response, $users);
    }

    /**
     * POST /api/admin/users
     *
     * Body (JSON): { email, display_name, password, role, github_username? }
     *
     * 201 — created
     * 409 — duplicate email
     * 422 — validation failure
     */
    public function createUser(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        $errors = $this->validate($body);
        if (!empty($errors)) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $email          = trim((string) $body['email']);
        $displayName    = trim((string) $body['display_name']);
        $password       = (string) $body['password'];
        $role           = (string) $body['role'];
        $githubUsername = isset($body['github_username']) && $body['github_username'] !== ''
            ? trim((string) $body['github_username'])
            : null;

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $newId = $this->userRepo->createFromHash($email, $displayName, $hash, $role, $githubUsername);
        } catch (\PDOException $e) {
            // SQLSTATE 23000 = integrity constraint violation (duplicate email)
            if (str_starts_with($e->getCode(), '23')) {
                return $this->json($response, ['error' => 'Email already in use.'], 409);
            }
            throw $e;
        }

        $user = $this->userRepo->findAll();
        // Return just the newly created user row
        $newUser = array_values(array_filter($user, fn(array $u): bool => (int) $u['id'] === $newId))[0] ?? null;

        return $this->json($response, $newUser, 201);
    }

    // -------------------------------------------------------------------------

    private function validate(array $body): array
    {
        $errors = [];

        $email = trim((string) ($body['email'] ?? ''));
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format.';
        }

        $displayName = trim((string) ($body['display_name'] ?? ''));
        if ($displayName === '') {
            $errors['display_name'] = 'Display name is required.';
        }

        $password = (string) ($body['password'] ?? '');
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        $role = (string) ($body['role'] ?? '');
        if (!in_array($role, ['admin', 'member'], true)) {
            $errors['role'] = 'Role must be admin or member.';
        }

        return $errors;
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status);
    }
}

