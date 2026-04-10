<?php
declare(strict_types=1);

/**
 * bootstrap/app.php — Slim 4 application factory.
 *
 * Creates, configures and returns the Slim App WITHOUT calling $app->run().
 * Shared by:
 *   public/index.php  → calls $app->run() after this file
 *   tests/bootstrap.php → tests call $app->handle($request) directly
 *
 * Expects environment variables to already be loaded (by the caller) before
 * this file is required so that config/settings.php reads correct values.
 */

use App\Middleware\CorsMiddleware;
use App\Middleware\JsonResponseMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Autoloader — safe to require multiple times (PHP dedups includes)
require_once __DIR__ . '/../vendor/autoload.php';

// ---------------------------------------------------------------------------
// DI container
// ---------------------------------------------------------------------------
$container = require __DIR__ . '/../config/container.php';

// ---------------------------------------------------------------------------
// Slim application
// ---------------------------------------------------------------------------
AppFactory::setContainer($container);
$app = AppFactory::create();

// ---------------------------------------------------------------------------
// Middleware (LIFO — last added runs first)
// ---------------------------------------------------------------------------
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(JsonResponseMiddleware::class);
$app->add(CorsMiddleware::class);

// Show detailed errors only in non-production environments
$settings      = $container->get('settings');
$displayErrors = $settings['app']['debug'];
$app->addErrorMiddleware($displayErrors, true, true);

// ---------------------------------------------------------------------------
// Base path for cPanel subdirectory installs
// ---------------------------------------------------------------------------
$basePath = $settings['app']['base_path'];
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// ---------------------------------------------------------------------------
// Route definitions
// ---------------------------------------------------------------------------
$routesFile = __DIR__ . '/../config/routes.php';
if (file_exists($routesFile)) {
    /** @psalm-suppress UnresolvableInclude */
    (require $routesFile)($app);
}

// ---------------------------------------------------------------------------
// Health check — always registered, used as smoke-test endpoint
// ---------------------------------------------------------------------------
$app->get('/api/health', function (Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR));
    return $response;
});

return $app;
