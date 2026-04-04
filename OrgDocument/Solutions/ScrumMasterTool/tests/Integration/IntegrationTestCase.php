<?php
declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * IntegrationTestCase — base class for all Slim 4 in-process integration tests.
 *
 * Key design decisions:
 *  - The Slim app is created once per test class (setUpBeforeClass) for speed.
 *  - The `users` table is truncated and re-seeded before each test so every
 *    test starts with a clean, predictable state.
 *  - PHP sessions run without cookies (ini_set in tests/bootstrap.php), so
 *    $app->handle() calls share $_SESSION within the same PHP process.
 *  - Session is cleared in tearDown() to prevent state leaking between tests.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static App $app;
    protected static PDO $pdo;

    // Credentials used by tests — match ADMIN_EMAIL / ADMIN_PASSWORD in .env.test
    protected static string $adminEmail    = '';
    protected static string $adminPassword = '';

    // =========================================================================
    // Suite-level setup (runs once per test class)
    // =========================================================================

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Resolve test admin credentials from env
        static::$adminEmail    = $_ENV['ADMIN_EMAIL']    ?? 'admin@test.local';
        static::$adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'Admin1234!';

        // Boot the Slim app using the shared factory
        static::$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';

        // Grab the PDO instance from the container for direct DB operations
        static::$pdo = static::$app->getContainer()->get(PDO::class);
    }

    // =========================================================================
    // Per-test setup / teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        // Clear users table and seed a fresh admin before every test
        static::$pdo->exec('DELETE FROM `users`');
        $this->seedAdmin(
            static::$adminEmail,
            static::$adminPassword,
            $_ENV['ADMIN_NAME'] ?? 'Test Admin',
        );

        // Reset PHP session state
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        } else {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear session so tests don't bleed state into each other
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
    }

    // =========================================================================
    // HTTP helpers
    // =========================================================================

    /**
     * Send an in-process HTTP request through the full Slim middleware stack.
     *
     * @param array<string, string> $headers
     */
    protected function request(
        string $method,
        string $path,
        mixed  $body    = null,
        array  $headers = [],
    ): ResponseInterface {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $path);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(
                is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR)
            );
            $request = $request
                ->withBody($stream)
                ->withHeader('Content-Type', 'application/json');
        }

        return static::$app->handle($request);
    }

    /**
     * Decode a response body as JSON and return it as an array.
     */
    protected function json(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    // =========================================================================
    // Auth helpers
    // =========================================================================

    /**
     * Perform a login request and return the response.
     * On success, $_SESSION['auth_user_id'] is populated for subsequent calls.
     */
    protected function login(string $email, string $password): ResponseInterface
    {
        return $this->request('POST', '/api/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    /**
     * Login as the test admin and assert it succeeded.
     */
    protected function loginAsAdmin(): ResponseInterface
    {
        $response = $this->login(static::$adminEmail, static::$adminPassword);
        $this->assertSame(200, $response->getStatusCode(), 'Admin login should succeed');
        return $response;
    }

    /**
     * Inject a specific user ID directly into $_SESSION, bypassing the login
     * flow. Useful for testing middleware behaviour without a full login sequence.
     */
    protected function authenticateSession(int $userId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['auth_user_id'] = $userId;
    }

    // =========================================================================
    // DB helpers
    // =========================================================================

    /**
     * Insert an admin user via direct PDO (mirrors seed.php logic).
     */
    protected function seedAdmin(string $email, string $password, string $name = 'Admin'): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = static::$pdo->prepare(
            'INSERT INTO `users` (email, password_hash, display_name, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $name, 'admin']);
        return (int) static::$pdo->lastInsertId();
    }

    /**
     * Insert a member user via direct PDO.
     */
    protected function seedMember(string $email, string $password, string $name = 'Member'): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = static::$pdo->prepare(
            'INSERT INTO `users` (email, password_hash, display_name, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $name, 'member']);
        return (int) static::$pdo->lastInsertId();
    }
}
