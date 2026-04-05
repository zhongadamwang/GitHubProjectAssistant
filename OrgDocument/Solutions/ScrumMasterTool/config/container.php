<?php
declare(strict_types=1);

/**
 * container.php — PHP-DI container factory for ScrumMasterTool.
 *
 * Registers:
 *  - 'settings'  → structured config array from config/settings.php
 *  - PDO         → MySQL connection built from settings['db']
 *
 * Returns the built ContainerInterface instance.
 *
 * Usage (in public/index.php):
 *   $container = require __DIR__ . '/../config/container.php';
 */

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BurndownController;
use App\Controllers\IssueController;
use App\Controllers\MemberController;
use App\Controllers\ProjectController;
use App\Controllers\SyncController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Repositories\BurndownRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\BurndownService;
use App\Services\EfficiencyService;
use App\Services\GitHubGraphQLService;
use App\Services\SyncService;
use App\Services\TimeTrackingService;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

$builder = new ContainerBuilder();

$builder->addDefinitions([

    // -------------------------------------------------------------------------
    // Settings — sourced from config/settings.php (reads $_ENV)
    // -------------------------------------------------------------------------
    'settings' => static fn(): array => require __DIR__ . '/settings.php',

    // -------------------------------------------------------------------------
    // PDO — MySQL connection (ADR-2)
    // Prepared statements only; exceptions on error; assoc fetch by default.
    // -------------------------------------------------------------------------
    PDO::class => static function (ContainerInterface $c): PDO {
        $db  = $c->get('settings')['db'];

        if (empty($db['name']) || empty($db['user'])) {
            throw new \RuntimeException(
                'DB_NAME and DB_USER must be set in .env before the PDO connection can be established.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        return new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    },

    // -------------------------------------------------------------------------
    // PSR-17 Response Factory (Slim\Psr7 implementation)
    // -------------------------------------------------------------------------
    ResponseFactoryInterface::class => static fn(): ResponseFactoryInterface => new ResponseFactory(),

    // -------------------------------------------------------------------------
    // Repositories
    // -------------------------------------------------------------------------
    UserRepository::class => static fn(ContainerInterface $c): UserRepository =>
        new UserRepository($c->get(PDO::class)),

    ProjectRepository::class => static fn(ContainerInterface $c): ProjectRepository =>
        new ProjectRepository($c->get(PDO::class)),

    IssueRepository::class => static fn(ContainerInterface $c): IssueRepository =>
        new IssueRepository($c->get(PDO::class)),

    SyncHistoryRepository::class => static fn(ContainerInterface $c): SyncHistoryRepository =>
        new SyncHistoryRepository($c->get(PDO::class)),

    BurndownRepository::class => static fn(ContainerInterface $c): BurndownRepository =>
        new BurndownRepository($c->get(PDO::class)),

    TimeLogRepository::class => static fn(ContainerInterface $c): TimeLogRepository =>
        new TimeLogRepository($c->get(PDO::class)),

    // -------------------------------------------------------------------------
    // Services
    // -------------------------------------------------------------------------
    GitHubGraphQLService::class => static function (ContainerInterface $c): GitHubGraphQLService {
        $github = $c->get('settings')['github'];

        if (empty($github['pat'])) {
            throw new \RuntimeException(
                'GITHUB_PAT must be set in .env to use GitHubGraphQLService.'
            );
        }

        return new GitHubGraphQLService(
            pat:      $github['pat'],
            endpoint: $github['graphql_url'],
        );
    },

    AuthService::class => static fn(ContainerInterface $c): AuthService =>
        new AuthService(
            $c->get(UserRepository::class),
            $c->get('settings')['session'],
        ),

    BurndownService::class => static fn(ContainerInterface $c): BurndownService =>
        new BurndownService(
            $c->get(BurndownRepository::class),
            $c->get(IssueRepository::class),
        ),

    EfficiencyService::class => static fn(ContainerInterface $c): EfficiencyService =>
        new EfficiencyService($c->get(IssueRepository::class)),

    TimeTrackingService::class => static fn(ContainerInterface $c): TimeTrackingService =>
        new TimeTrackingService(
            $c->get(TimeLogRepository::class),
            $c->get(IssueRepository::class),
            $c->get(PDO::class),
        ),

    SyncService::class => static function (ContainerInterface $c): SyncService {
        $github = $c->get('settings')['github'];
        $sync   = $c->get('settings')['sync'];

        return new SyncService(
            gitHub:          $c->get(GitHubGraphQLService::class),
            projectRepo:     $c->get(ProjectRepository::class),
            issueRepo:       $c->get(IssueRepository::class),
            historyRepo:     $c->get(SyncHistoryRepository::class),
            owner:           $github['org'],
            projectNumber:   $github['project_number'],
            snapshotDir:     $sync['snapshot_dir'],
            burndownService: $c->get(BurndownService::class),
        );
    },

    // -------------------------------------------------------------------------
    // Controllers
    // -------------------------------------------------------------------------
    AuthController::class => static fn(ContainerInterface $c): AuthController =>
        new AuthController($c->get(AuthService::class)),

    ProjectController::class => static fn(ContainerInterface $c): ProjectController =>
        new ProjectController(
            $c->get(ProjectRepository::class),
            $c->get(IssueRepository::class),
        ),

    IssueController::class => static fn(ContainerInterface $c): IssueController =>
        new IssueController(
            $c->get(IssueRepository::class),
            $c->get(TimeTrackingService::class),
        ),

    BurndownController::class => static fn(ContainerInterface $c): BurndownController =>
        new BurndownController(
            $c->get(BurndownService::class),
            $c->get(BurndownRepository::class),
        ),

    MemberController::class => static fn(ContainerInterface $c): MemberController =>
        new MemberController($c->get(EfficiencyService::class)),

    AdminController::class => static fn(ContainerInterface $c): AdminController =>
        new AdminController($c->get(UserRepository::class)),

    SyncController::class => static fn(ContainerInterface $c): SyncController =>
        new SyncController(
            $c->get(SyncService::class),
            $c->get(SyncHistoryRepository::class),
        ),

    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------
    AuthMiddleware::class => static fn(ContainerInterface $c): AuthMiddleware =>
        new AuthMiddleware(
            $c->get(AuthService::class),
            $c->get(ResponseFactoryInterface::class),
        ),

    AdminMiddleware::class => static fn(ContainerInterface $c): AdminMiddleware =>
        new AdminMiddleware($c->get(ResponseFactoryInterface::class)),

]);

return $builder->build();
