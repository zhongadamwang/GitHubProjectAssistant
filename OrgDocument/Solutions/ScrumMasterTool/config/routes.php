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
 *   GET   /api/projects/{id}/issues   (paginated, read-only)
 *   GET   /api/projects/{id}/iterations
 *   GET   /api/projects/{id}/burndown
 *   GET   /api/projects/{id}/members
 *   GET   /api/sync/history
 *   POST  /api/sync/trigger       (admin; legacy alias — first tracked project)
 *   POST  /api/projects/{id}/sync (admin; one project per request)
 *   GET   /api/admin/users        (admin)
 *   POST  /api/admin/users        (admin)
 *   POST /api/admin/github-accounts/import-env (admin; legacy .env → account)
 *   GET|POST /api/admin/github-accounts, PUT|DELETE …/{id}, POST …/{id}/test, GET …/{id}/projects (admin)
 *   POST /api/admin/projects, PUT|DELETE …/{id}, DELETE …/{id}/data, POST …/{id}/validate-fields (admin)
 *   GET   /api/admin/audit-log    (admin)
 */

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BurndownController;
use App\Controllers\GitHubAccountController;
use App\Controllers\IssueController;
use App\Controllers\MemberController;
use App\Controllers\ProjectAdminController;
use App\Controllers\ProjectController;
use App\Controllers\SyncController;
use App\Middleware\AdminMiddleware;
use App\Middleware\RequireJsonMiddleware;
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
        $group->get('/projects/{id:[0-9]+}/issues',  [IssueController::class,   'getIssues']);
        $group->get('/projects/{id:[0-9]+}/iterations', [ProjectController::class, 'getIterations']);
        $group->get('/projects/{id:[0-9]+}/burndown', [BurndownController::class, 'getBurndown']);
        $group->get('/projects/{id:[0-9]+}/members', [MemberController::class, 'getMembers']);

        // Sync
        $group->get('/sync/history', [SyncController::class, 'history']);

    })->add(AuthMiddleware::class);

    // =========================================================================
    // Admin routes — valid session + admin role required
    // AuthMiddleware runs first (outer), AdminMiddleware runs second (inner).
    // =========================================================================
    $app->group('/api', function (RouteCollectorProxy $group): void {

        $group->post('/sync/trigger',  [SyncController::class,  'trigger']);
        $group->post('/projects/{id:[0-9]+}/sync', [SyncController::class, 'syncProject']);
        $group->get('/admin/users',    [AdminController::class, 'listUsers']);
        $group->post('/admin/users',   [AdminController::class, 'createUser']);

        // GitHub accounts (tokens are write-only; see GitHubAccountController)
        $group->get('/admin/github-accounts',                          [GitHubAccountController::class, 'list']);
        $group->post('/admin/github-accounts',                         [GitHubAccountController::class, 'create']);
        $group->post('/admin/github-accounts/import-env',              [GitHubAccountController::class, 'importEnv']);
        $group->put('/admin/github-accounts/{id:[0-9]+}',              [GitHubAccountController::class, 'update']);
        $group->delete('/admin/github-accounts/{id:[0-9]+}',           [GitHubAccountController::class, 'delete']);
        $group->post('/admin/github-accounts/{id:[0-9]+}/test',        [GitHubAccountController::class, 'test']);
        $group->get('/admin/github-accounts/{id:[0-9]+}/projects',     [GitHubAccountController::class, 'discover']);
        // Tracked projects (REQ-ADD-003)
        $group->post('/admin/projects',                                [ProjectAdminController::class, 'track']);
        $group->put('/admin/projects/{id:[0-9]+}',                     [ProjectAdminController::class, 'update']);
        $group->delete('/admin/projects/{id:[0-9]+}',                  [ProjectAdminController::class, 'untrack']);
        $group->delete('/admin/projects/{id:[0-9]+}/data',             [ProjectAdminController::class, 'deleteData']);
        $group->post('/admin/projects/{id:[0-9]+}/validate-fields',    [ProjectAdminController::class, 'validateFields']);
        $group->get('/admin/audit-log',                                [GitHubAccountController::class, 'auditLog']);

    })->add(RequireJsonMiddleware::class)
      ->add(AdminMiddleware::class)
      ->add(AuthMiddleware::class);

};
