<?php
declare(strict_types=1);

namespace Tests\Integration\Phase1;

use Tests\Integration\IntegrationTestCase;

/**
 * MiddlewareTest — verifies that route protection middleware works correctly (T005, T006).
 *
 * Covers:
 *  - AuthMiddleware: unauthenticated requests to protected routes → 401
 *  - AdminMiddleware: authenticated member requests to admin routes → 403
 *  - Admin authenticated requests to admin routes → pass through (501 from placeholder)
 *  - All 13 routes are reachable (correct HTTP method + path registered)
 */
final class MiddlewareTest extends IntegrationTestCase
{
    // =========================================================================
    // Authentication guard (AuthMiddleware → 401)
    // =========================================================================

    /**
     * @dataProvider protectedRouteProvider
     */
    public function test_unauthenticated_request_to_protected_route_returns_401(
        string $method,
        string $path,
    ): void {
        $response = $this->request($method, $path);

        $this->assertSame(
            401,
            $response->getStatusCode(),
            "Route {$method} {$path} should return 401 when unauthenticated",
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function protectedRouteProvider(): array
    {
        return [
            'logout'             => ['POST', '/api/auth/logout'],
            'me'                 => ['GET',  '/api/auth/me'],
            'projects list'      => ['GET',  '/api/projects'],
            'project detail'     => ['GET',  '/api/projects/1'],
            'project issues'     => ['GET',  '/api/projects/1/issues'],
            'project burndown'   => ['GET',  '/api/projects/1/burndown'],
            'project members'    => ['GET',  '/api/projects/1/members'],
            'issue time update'  => ['PUT',  '/api/issues/1/time'],
            'sync history'       => ['GET',  '/api/sync/history'],
        ];
    }

    // =========================================================================
    // Admin guard (AdminMiddleware → 403) for member role
    // =========================================================================

    /**
     * @dataProvider adminRouteProvider
     */
    public function test_member_user_on_admin_route_returns_403(
        string $method,
        string $path,
    ): void {
        // Seed a member and authenticate as them
        $memberId = $this->seedMember('member@test.local', 'Member1234!');
        $this->authenticateSession($memberId);

        $response = $this->request($method, $path);

        $this->assertSame(
            403,
            $response->getStatusCode(),
            "Route {$method} {$path} should return 403 for member role",
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminRouteProvider(): array
    {
        return [
            'sync trigger'  => ['POST', '/api/sync/trigger'],
            'admin users'   => ['GET',  '/api/admin/users'],
            'create user'   => ['POST', '/api/admin/users'],
        ];
    }

    // =========================================================================
    // Admin user passes admin guard (gets 501 from placeholder controller)
    // =========================================================================

    /**
     * @dataProvider adminRouteProvider
     */
    public function test_admin_user_on_admin_route_passes_guard(
        string $method,
        string $path,
    ): void {
        $this->loginAsAdmin();

        $response = $this->request($method, $path);

        // 501 means middleware passed and the placeholder controller responded
        $this->assertSame(
            501,
            $response->getStatusCode(),
            "Route {$method} {$path} should reach placeholder (501) for admin user",
        );
    }

    // =========================================================================
    // Authenticated user passes auth guard on protected routes
    // =========================================================================

    public function test_authenticated_user_reaches_projects_placeholder(): void
    {
        $this->loginAsAdmin();

        $response = $this->request('GET', '/api/projects');

        // 501 = middleware passed, placeholder controller responded
        $this->assertSame(501, $response->getStatusCode());
    }

    // =========================================================================
    // Response shape checks
    // =========================================================================

    public function test_401_response_has_json_content_type(): void
    {
        $response = $this->request('GET', '/api/projects');

        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function test_401_response_body_has_error_key(): void
    {
        $response = $this->request('GET', '/api/projects');
        $body     = $this->json($response);

        $this->assertArrayHasKey('error', $body);
    }

    public function test_403_response_body_has_error_key(): void
    {
        $memberId = $this->seedMember('member2@test.local', 'Member1234!');
        $this->authenticateSession($memberId);

        $response = $this->request('GET', '/api/admin/users');
        $body     = $this->json($response);

        $this->assertArrayHasKey('error', $body);
    }

    // =========================================================================
    // Public route login is accessible without authentication
    // =========================================================================

    public function test_login_endpoint_is_publicly_accessible(): void
    {
        // Should NOT return 401 — even with bad credentials (gets 400 for empty body)
        $response = $this->request('POST', '/api/auth/login', []);

        $this->assertNotSame(401, $response->getStatusCode());
    }
}
