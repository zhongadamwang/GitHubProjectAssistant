<?php
declare(strict_types=1);

namespace Tests\Integration\Phase1;

use Tests\Integration\IntegrationTestCase;

/**
 * AuthTest — exercises the full authentication lifecycle (T005).
 *
 * Covers:
 *  - POST /api/auth/login  (success, bad password, bad email format, missing fields)
 *  - GET  /api/auth/me
 *  - POST /api/auth/logout
 *  - Response shape: id, email, display_name, role — never password_hash
 *  - Generic 401 error message (no user enumeration)
 */
final class AuthTest extends IntegrationTestCase
{
    // =========================================================================
    // Login — success
    // =========================================================================

    public function test_login_with_valid_credentials_returns_200(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_login_response_contains_user_object(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);
        $body     = $this->json($response);

        $this->assertArrayHasKey('user', $body);
    }

    public function test_login_response_user_has_expected_keys(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);
        $user     = $this->json($response)['user'];

        $this->assertArrayHasKey('id',           $user);
        $this->assertArrayHasKey('email',        $user);
        $this->assertArrayHasKey('display_name', $user);
        $this->assertArrayHasKey('role',         $user);
    }

    public function test_login_response_does_not_expose_password_hash(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);
        $body     = $this->json($response);

        $bodyString = json_encode($body);
        $this->assertStringNotContainsString('password', $bodyString);
        $this->assertStringNotContainsString('$2y$',     $bodyString);
    }

    public function test_login_returns_correct_email_and_role(): void
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);
        $user     = $this->json($response)['user'];

        $this->assertSame(static::$adminEmail, $user['email']);
        $this->assertSame('admin',             $user['role']);
    }

    public function test_login_sets_session_user_id(): void
    {
        $this->login(static::$adminEmail, static::$adminPassword);

        $this->assertArrayHasKey('auth_user_id', $_SESSION);
        $this->assertIsInt($_SESSION['auth_user_id']);
    }

    // =========================================================================
    // Login — failure cases
    // =========================================================================

    public function test_login_with_wrong_password_returns_401(): void
    {
        $response = $this->login(static::$adminEmail, 'wrongpassword');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->login('nobody@example.com', 'irrelevant');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_login_error_message_is_generic_no_enumeration(): void
    {
        // Both bad-password and no-such-user must return the SAME message
        $wrongPass  = $this->json($this->login(static::$adminEmail, 'x'))['error']   ?? '';
        $noSuchUser = $this->json($this->login('ghost@example.com', 'x'))['error']    ?? '';

        $this->assertSame($wrongPass, $noSuchUser, 'Error messages must be identical to prevent user enumeration');
        $this->assertSame('Invalid credentials.', $wrongPass);
    }

    public function test_login_with_missing_email_returns_400(): void
    {
        $response = $this->request('POST', '/api/auth/login', ['password' => 'x']);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_login_with_missing_password_returns_400(): void
    {
        $response = $this->request('POST', '/api/auth/login', ['email' => static::$adminEmail]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_login_with_invalid_email_format_returns_400(): void
    {
        $response = $this->login('not-an-email', 'pass');

        $this->assertSame(400, $response->getStatusCode());
    }

    // =========================================================================
    // GET /api/auth/me
    // =========================================================================

    public function test_me_after_login_returns_200(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('GET', '/api/auth/me');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_me_returns_same_user_as_login(): void
    {
        $loginUser = $this->json($this->loginAsAdmin())['user'];

        $meUser = $this->json($this->request('GET', '/api/auth/me'))['user'];

        $this->assertSame($loginUser['id'],    $meUser['id']);
        $this->assertSame($loginUser['email'], $meUser['email']);
    }

    public function test_me_without_session_returns_401(): void
    {
        // Ensure no session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }

        $response = $this->request('GET', '/api/auth/me');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_me_response_does_not_expose_password_hash(): void
    {
        $this->loginAsAdmin();

        $body = json_encode($this->json($this->request('GET', '/api/auth/me')));

        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('$2y$',     $body);
    }

    // =========================================================================
    // POST /api/auth/logout
    // =========================================================================

    public function test_logout_after_login_returns_200(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/auth/logout');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_me_after_logout_returns_401(): void
    {
        $this->loginAsAdmin();
        $this->request('POST', '/api/auth/logout');

        $response = $this->request('GET', '/api/auth/me');

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_session_is_cleared_after_logout(): void
    {
        $this->loginAsAdmin();
        $this->request('POST', '/api/auth/logout');

        $this->assertArrayNotHasKey('auth_user_id', $_SESSION ?? []);
    }
}
