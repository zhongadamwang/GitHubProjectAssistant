<?php

declare(strict_types=1);

namespace Tests\Integration\Phase3;

use App\Controllers\AdminController;
use App\Models\User;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;

/**
 * AdminControllerTest — unit-style tests for AdminController.
 *
 * Uses PHPUnit mock objects for UserRepository and Request.
 * Uses real Slim\Psr7\Response so that withStatus() and getBody()->write()
 * work exactly as they do at runtime.
 *
 * Covers:
 *   (a) listUsers() returns 200 with users array — password_hash absent
 *   (b) createUser() with valid data returns 201 with user payload
 *   (c) createUser() with duplicate email (PDOException 1062) returns 409
 *   (d) createUser() with invalid email format returns 422 with errors map
 *   (e) createUser() with password shorter than 8 chars returns 422
 *   (f) createUser() with invalid role returns 422
 *   (g) createUser() with missing display_name returns 422
 *
 * Run with:
 *   vendor/phpunit/phpunit/phpunit --testsuite "Phase3 Unit"
 */
final class AdminControllerTest extends TestCase
{
    // =========================================================================
    // (a) listUsers — returns 200, users array, no password_hash
    // =========================================================================

    public function testListUsersReturns200WithUsersArray(): void
    {
        $rows = [
            [
                'id'             => 1,
                'email'          => 'alice@example.com',
                'display_name'   => 'Alice',
                'role'           => 'admin',
                'github_username'=> 'alice-gh',
                'created_at'     => '2026-04-01 10:00:00',
                'updated_at'     => '2026-04-01 10:00:00',
                // password_hash intentionally absent — findAll() never selects it
            ],
        ];

        $repo    = $this->createMock(UserRepository::class);
        $repo->method('findAll')->willReturn($rows);

        $controller = new AdminController($repo);
        $response   = $controller->listUsers($this->makeRequest([]), new Response());

        $this->assertSame(200, $response->getStatusCode());

        $body    = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertArrayHasKey('users', $decoded);
        $this->assertCount(1, $decoded['users']);
        $this->assertSame('alice@example.com', $decoded['users'][0]['email']);
        $this->assertArrayNotHasKey('password_hash', $decoded['users'][0]);
    }

    // =========================================================================
    // (b) createUser — valid data returns 201 with user payload
    // =========================================================================

    public function testCreateUserValidDataReturns201(): void
    {
        $user = new User(
            id:             42,
            email:          'bob@example.com',
            displayName:    'Bob',
            role:           'member',
            githubUsername: null,
            createdAt:      '2026-04-04 12:00:00',
        );

        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
             ->method('create')
             ->with('bob@example.com', 'securepassword', 'Bob', 'member', null)
             ->willReturn($user);

        $body = [
            'email'        => 'bob@example.com',
            'display_name' => 'Bob',
            'password'     => 'securepassword',
            'role'         => 'member',
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(201, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('user', $decoded);
        $this->assertSame(42,                  $decoded['user']['id']);
        $this->assertSame('bob@example.com',   $decoded['user']['email']);
        $this->assertSame('Bob',               $decoded['user']['display_name']);
        $this->assertArrayNotHasKey('password_hash', $decoded['user']);
    }

    // =========================================================================
    // (c) createUser — duplicate email returns 409
    // =========================================================================

    public function testCreateUserDuplicateEmailReturns409(): void
    {
        $exception = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');
        $exception->errorInfo = ['23000', 1062, 'Duplicate entry'];

        $repo = $this->createMock(UserRepository::class);
        $repo->method('create')->willThrowException($exception);

        $body = [
            'email'        => 'existing@example.com',
            'display_name' => 'Existing',
            'password'     => 'password123',
            'role'         => 'member',
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(409, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertStringContainsStringIgnoringCase('already in use', $decoded['error']);
    }

    // =========================================================================
    // (d) createUser — invalid email format returns 422
    // =========================================================================

    public function testCreateUserInvalidEmailReturns422(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->never())->method('create');

        $body = [
            'email'        => 'not-an-email',
            'display_name' => 'Tester',
            'password'     => 'password123',
            'role'         => 'member',
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertArrayHasKey('email', $decoded['errors']);
    }

    // =========================================================================
    // (e) createUser — password < 8 chars returns 422
    // =========================================================================

    public function testCreateUserShortPasswordReturns422(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->never())->method('create');

        $body = [
            'email'        => 'valid@example.com',
            'display_name' => 'Tester',
            'password'     => 'short',   // only 5 chars
            'role'         => 'member',
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertArrayHasKey('password', $decoded['errors']);
    }

    // =========================================================================
    // (f) createUser — invalid role returns 422
    // =========================================================================

    public function testCreateUserInvalidRoleReturns422(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->never())->method('create');

        $body = [
            'email'        => 'valid@example.com',
            'display_name' => 'Tester',
            'password'     => 'password123',
            'role'         => 'superuser',   // not 'admin' or 'member'
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertArrayHasKey('role', $decoded['errors']);
    }

    // =========================================================================
    // (g) createUser — missing display_name returns 422
    // =========================================================================

    public function testCreateUserMissingDisplayNameReturns422(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->never())->method('create');

        $body = [
            'email'    => 'valid@example.com',
            'password' => 'password123',
            'role'     => 'member',
            // 'display_name' intentionally absent
        ];

        $controller = new AdminController($repo);
        $response   = $controller->createUser($this->makeRequest($body), new Response());

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('errors', $decoded);
        $this->assertArrayHasKey('display_name', $decoded['errors']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a minimal Request mock whose getParsedBody() returns $body.
     *
     * @param array<string,mixed> $body
     * @return Request&\PHPUnit\Framework\MockObject\MockObject
     */
    private function makeRequest(array $body): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getParsedBody')->willReturn($body);

        return $request;
    }
}
