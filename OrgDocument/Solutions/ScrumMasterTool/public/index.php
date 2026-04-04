<?php
declare(strict_types=1);

/**
 * index.php — Slim 4 front controller for ScrumMasterTool.
 *
 * All HTTP requests are rewritten here by public/.htaccess.
 *
 * Boot order:
 *   1. Composer autoloader
 *   2. .env via vlucas/phpdotenv
 *   3. DI container (config/container.php)
 *   4. Slim app factory
 *   5. Middleware stack: CORS → JSON Content-Type → routing → error handling
 *   6. Route definitions (config/routes.php)
 *   7. Health check route
 *   8. App run
 */

use App\Middleware\CorsMiddleware;
use App\Middleware\JsonResponseMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// ---------------------------------------------------------------------------
// 1. Autoloader
// ---------------------------------------------------------------------------
require dirname(__DIR__) . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// 2. Load .env
// ---------------------------------------------------------------------------
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($envPath);
    $dotenv->load();
}

// ---------------------------------------------------------------------------
// 3. DI container
// ---------------------------------------------------------------------------
$container = require dirname(__DIR__) . '/config/container.php';

// ---------------------------------------------------------------------------
// 4. Slim application
// ---------------------------------------------------------------------------
AppFactory::setContainer($container);
$app = AppFactory::create();

// ---------------------------------------------------------------------------
// 5. Middleware stack
//    Middleware added last executes first (LIFO):
//      CorsMiddleware      — outermost: handles preflight before anything else
//      JsonResponseMiddleware — sets Content-Type on all responses
//      RoutingMiddleware   — Slim route resolution
//      BodyParsingMiddleware — parses JSON / form request bodies
// ---------------------------------------------------------------------------
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(JsonResponseMiddleware::class);
$app->add(CorsMiddleware::class);

// Error middleware — show details only in development
$settings      = $container->get('settings');
$displayErrors = $settings['app']['debug'];
$app->addErrorMiddleware($displayErrors, true, true);

// ---------------------------------------------------------------------------
// 6. Base path for subdirectory installs (cPanel)
// ---------------------------------------------------------------------------
$basePath = $settings['app']['base_path'];
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// ---------------------------------------------------------------------------
// 7. Route definitions
// ---------------------------------------------------------------------------
$routesFile = dirname(__DIR__) . '/config/routes.php';
if (file_exists($routesFile)) {
    /** @psalm-suppress UnresolvableInclude */
    (require $routesFile)($app);
}

// ---------------------------------------------------------------------------
// 8. Health check — always registered here as a reliable smoke-test endpoint
//    GET /api/health → {"status":"ok"}
// ---------------------------------------------------------------------------
$app->get('/api/health', static function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
    return $response;
});

// ---------------------------------------------------------------------------
// 9. Run
// ---------------------------------------------------------------------------
$app->run();
