<?php
declare(strict_types=1);

/**
 * routes.php — Slim 4 route definitions for ScrumMasterTool.
 *
 * Returns a callable that accepts the Slim App instance.
 * Loaded from public/index.php via:
 *   (require __DIR__ . '/../config/routes.php')($app);
 *
 * Route groups:
 *   Public     — no auth required
 *   Auth       — requires valid session (AuthMiddleware → 401)
 *   Admin      — requires admin role  (AuthMiddleware + AdminMiddleware → 401/403)
 *
 * Endpoint inventory (per technical-architecture.md):
 *   POST  /api/auth/login
 *   POST  /api/auth/logout
 *   GET   /api/auth/me
 *   GET   /api/projects
 *   GET   /api/projects/{id}
 *   GET   /api/projects/{id}/issues
 *   PUT   /api/issues/{id}/time
 *   GET   /api/projects/{id}/burndown
 *   GET   /api/projects/{id}/members
 *   GET   /api/sync/history
 *   POST  /api/sync/trigger       (admin)
 *   GET   /api/admin/users        (admin)
 *   POST  /api/admin/users        (admin)
 */

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\IssueController;
use App\Controllers\ProjectController;
use App\Controllers\SyncController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {

    // =========================================================================
    // Public routes — no authentication required
    // =========================================================================
    $app->group('/api/auth', function (RouteCollectorProxy $group): void {
        $group->post('/login', [AuthController::class, 'login']);
    });

    // =========================================================================
    // Authenticated routes — valid session required
    // AuthMiddleware fires for every route in this group.
    // =========================================================================
    $app->group('/api', function (RouteCollectorProxy $group): void {

        // Auth endpoints
        $group->post('/auth/logout', [AuthController::class, 'logout']);
        $group->get('/auth/me',      [AuthController::class, 'me']);

        // Projects
        $group->get('/projects',                    [ProjectController::class, 'index']);
        $group->get('/projects/{id:[0-9]+}',        [ProjectController::class, 'show']);
        $group->get('/projects/{id:[0-9]+}/issues',  [ProjectController::class, 'issues']);
        $group->get('/projects/{id:[0-9]+}/burndown',[ProjectController::class, 'burndown']);
        $group->get('/projects/{id:[0-9]+}/members', [ProjectController::class, 'members']);

        // Issues
        $group->put('/issues/{id:[0-9]+}/time', [IssueController::class, 'updateTime']);

        // Sync
        $group->get('/sync/history', [SyncController::class, 'history']);

    })->add(AuthMiddleware::class);

    // =========================================================================
    // Admin routes — valid session + admin role required
    // AuthMiddleware runs first (outer), AdminMiddleware runs second (inner).
    // =========================================================================
    $app->group('/api', function (RouteCollectorProxy $group): void {

        $group->post('/sync/trigger',  [SyncController::class,  'trigger']);
        $group->get('/admin/users',    [AdminController::class, 'listUsers']);
        $group->post('/admin/users',   [AdminController::class, 'createUser']);

    })->add(AdminMiddleware::class)
      ->add(AuthMiddleware::class);

};
