<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use Tests\Integration\IntegrationTestCase;

/**
 * AdminTest — covers GET /api/admin/users and POST /api/admin/users (T017).
 *
 * Both routes require admin role (AdminMiddleware + AuthMiddleware).
 */
final class AdminTest extends IntegrationTestCase
{
    // =========================================================================
    // GET /api/admin/users
    // =========================================================================

    public function test_list_users_returns_200_for_admin(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('GET', '/api/admin/users');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_list_users_returns_array(): void
    {
        $this->loginAsAdmin();

        $body = $this->json($this->request('GET', '/api/admin/users'));

        $this->assertIsArray($body);
    }

    public function test_list_users_does_not_expose_password_hash(): void
    {
        $this->loginAsAdmin();

        $body       = $this->json($this->request('GET', '/api/admin/users'));
        $bodyString = json_encode($body);

        $this->assertStringNotContainsString('password_hash', $bodyString);
        $this->assertStringNotContainsString('$2y$', $bodyString);
    }

    public function test_list_users_returns_401_when_unauthenticated(): void
    {
        $response = $this->request('GET', '/api/admin/users');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_list_users_returns_403_for_member(): void
    {
        $memberId = $this->seedMember('m@test.local', 'Member1234!');
        $this->authenticateSession($memberId);

        $response = $this->request('GET', '/api/admin/users');
        $this->assertSame(403, $response->getStatusCode());
    }

    // =========================================================================
    // POST /api/admin/users
    // =========================================================================

    public function test_create_user_returns_201_with_valid_data(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'newuser@test.local',
            'display_name' => 'New User',
            'password'     => 'Password1!',
            'role'         => 'member',
        ]);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_create_user_response_contains_expected_fields(): void
    {
        $this->loginAsAdmin();

        $body = $this->json($this->request('POST', '/api/admin/users', [
            'email'        => 'newuser2@test.local',
            'display_name' => 'Another User',
            'password'     => 'Password1!',
            'role'         => 'member',
        ]));

        $this->assertArrayHasKey('id',           $body);
        $this->assertArrayHasKey('email',        $body);
        $this->assertArrayHasKey('display_name', $body);
        $this->assertArrayHasKey('role',         $body);
    }

    public function test_create_user_does_not_expose_password_hash(): void
    {
        $this->loginAsAdmin();

        $body       = $this->json($this->request('POST', '/api/admin/users', [
            'email'        => 'newuser3@test.local',
            'display_name' => 'Pwd Test',
            'password'     => 'Password1!',
            'role'         => 'member',
        ]));
        $bodyString = json_encode($body);

        $this->assertStringNotContainsString('password_hash', $bodyString);
        $this->assertStringNotContainsString('$2y$', $bodyString);
    }

    public function test_create_user_returns_409_on_duplicate_email(): void
    {
        $this->loginAsAdmin();

        $payload = [
            'email'        => static::$adminEmail,  // already seeded
            'display_name' => 'Dup',
            'password'     => 'Password1!',
            'role'         => 'member',
        ];

        $response = $this->request('POST', '/api/admin/users', $payload);
        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_create_user_returns_422_on_invalid_email(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'not-an-email',
            'display_name' => 'X',
            'password'     => 'Password1!',
            'role'         => 'member',
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_create_user_returns_422_on_short_password(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'short@test.local',
            'display_name' => 'Short',
            'password'     => 'abc',
            'role'         => 'member',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $body = $this->json($response);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function test_create_user_returns_422_on_invalid_role(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'badrole@test.local',
            'display_name' => 'BadRole',
            'password'     => 'Password1!',
            'role'         => 'superuser',
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_create_user_returns_401_when_unauthenticated(): void
    {
        $response = $this->request('POST', '/api/admin/users', [
            'email'        => 'x@x.com',
            'display_name' => 'X',
            'password'     => 'Password1!',
            'role'         => 'member',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }
}
