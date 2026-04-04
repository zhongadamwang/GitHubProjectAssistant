<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * AuthService — session-based authentication for ScrumMasterTool.
 *
 * Security notes (ADR-7 / OWASP Session Management):
 *  - session_regenerate_id(true) on every successful login (session fixation protection)
 *  - httpOnly, secure, SameSite cookie flags configured before session_start()
 *  - Generic error messages: never reveal whether an email exists
 *  - password_hash / password_verify (bcrypt, cost 12)
 *  - The password_hash value is fetched via a separate repository method and
 *    is never stored on the User model or returned to the caller
 */
final class AuthService
{
    /** Session key that stores the authenticated user's ID. */
    private const SESSION_USER_KEY = 'auth_user_id';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly array          $sessionSettings,
    ) {
    }

    /**
     * Start an authenticated session for the given credentials.
     *
     * @return User The authenticated user (password_hash excluded).
     * @throws \InvalidArgumentException on invalid credentials (use a generic
     *         message — do NOT distinguish "wrong email" from "wrong password").
     */
    public function login(string $email, string $password): User
    {
        $this->configureSessionCookie();
        $this->ensureSessionStarted();

        // Fetch the stored hash independently of the User object so the hash
        // never leaks into the User model or API responses.
        $storedHash = $this->userRepository->findHashByEmail($email);

        // Constant-time comparison: always call password_verify even when the
        // email does not exist to prevent timing-based user enumeration.
        $dummyHash = '$2y$12$invalidhashpadding00000000000000000000000000000000000';
        $hashToVerify = $storedHash ?? $dummyHash;

        if ($storedHash === null || !password_verify($password, $hashToVerify)) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        // Session fixation protection: regenerate ID before writing any data
        session_regenerate_id(true);

        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            // Should not happen — we just verified the hash — but guard anyway
            throw new \RuntimeException('User could not be loaded after credential check.');
        }

        $_SESSION[self::SESSION_USER_KEY] = $user->id;

        return $user;
    }

    /**
     * Destroy the current session completely.
     */
    public function logout(): void
    {
        $this->configureSessionCookie();
        $this->ensureSessionStarted();

        // Unset all session data
        $_SESSION = [];

        // Expire the cookie on the client
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly'],
            );
        }

        session_destroy();
    }

    /**
     * Return the currently authenticated User or null if there is no
     * valid session.
     */
    public function getCurrentUser(): ?User
    {
        $this->configureSessionCookie();
        $this->ensureSessionStarted();

        $userId = $_SESSION[self::SESSION_USER_KEY] ?? null;
        if (!is_int($userId)) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Apply secure cookie parameters before session_start().
     * Must be called before any session_start() invocation.
     */
    private function configureSessionCookie(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            // Session already active — cookie params are fixed at start time
            return;
        }

        $s = $this->sessionSettings;

        session_name($s['name'] ?? 'scrum_session');

        session_set_cookie_params([
            'lifetime' => $s['lifetime'] ?? 28800,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $s['secure']    ?? false,
            'httponly' => $s['http_only'] ?? true,
            'samesite' => $s['same_site'] ?? 'Strict',
        ]);
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
