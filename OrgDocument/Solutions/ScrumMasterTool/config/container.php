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
use App\Controllers\GitHubAccountController;
use App\Controllers\IssueController;
use App\Controllers\ProjectAdminController;
use App\Controllers\MemberController;
use App\Controllers\ProjectController;
use App\Controllers\SyncController;
use App\Middleware\AdminMiddleware;
use App\Middleware\RequireJsonMiddleware;
use App\Middleware\AuthMiddleware;
use App\Repositories\AuditLogRepository;
use App\Repositories\BurndownRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Repositories\TimeLogRepository;
use App\Repositories\UserRepository;
use App\Services\AccountConnectionTester;
use App\Services\AuthService;
use App\Services\BurndownService;
use App\Services\CredentialVault;
use App\Services\EnvAccountImporter;
use App\Services\EfficiencyService;
use App\Services\GitHubClientFactory;
use App\Services\GitHubClientFactoryInterface;
use App\Services\GraphqlEndpointValidator;
use App\Services\SyncOrchestrator;
use App\Services\SyncServiceFactory;
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

    GitHubAccountRepository::class => static fn(ContainerInterface $c): GitHubAccountRepository =>
        new GitHubAccountRepository($c->get(PDO::class)),

    AuditLogRepository::class => static fn(ContainerInterface $c): AuditLogRepository =>
        new AuditLogRepository($c->get(PDO::class)),

    TimeLogRepository::class => static fn(ContainerInterface $c): TimeLogRepository =>
        new TimeLogRepository($c->get(PDO::class)),

    // -------------------------------------------------------------------------
    // Services
    // -------------------------------------------------------------------------
    // Lazy: constructing the vault never fails; using it without APP_KEY does
    CredentialVault::class => static fn(ContainerInterface $c): CredentialVault =>
        new CredentialVault((string) $c->get('settings')['security']['app_key']),

    GitHubClientFactoryInterface::class => static fn(ContainerInterface $c): GitHubClientFactoryInterface =>
        new GitHubClientFactory($c->get(CredentialVault::class)),

    AccountConnectionTester::class => static fn(): AccountConnectionTester => new AccountConnectionTester(),

    GraphqlEndpointValidator::class => static fn(): GraphqlEndpointValidator => new GraphqlEndpointValidator(),

    AuthService::class => static fn(ContainerInterface $c): AuthService =>
        new AuthService(
            $c->get(UserRepository::class),
            $c->get('settings')['session'],
        ),

    SyncServiceFactory::class => static function (ContainerInterface $c): SyncServiceFactory {
        $settings = $c->get('settings');

        return new SyncServiceFactory(
            $c->get(ProjectRepository::class),
            $c->get(IssueRepository::class),
            $c->get(SyncHistoryRepository::class),
            $c->get(BurndownService::class),
            $c->get(TimeLogRepository::class),
            $c->get(GitHubClientFactoryInterface::class),
            $settings['github']['fields'],
            $settings['sync']['snapshot_dir'],
            (int) $settings['sync']['snapshot_retention'],
        );
    },

    SyncOrchestrator::class => static function (ContainerInterface $c): SyncOrchestrator {
        $github = $c->get('settings')['github'];

        return new SyncOrchestrator(
            $c->get(ProjectRepository::class),
            $c->get(GitHubAccountRepository::class),
            $c->get(SyncHistoryRepository::class),
            $c->get(SyncServiceFactory::class),
            $c->get(AccountConnectionTester::class),
            [
                'pat'            => (string) $github['pat'],
                'org'            => (string) $github['org'],
                'project_number' => (int) $github['project_number'],
                'graphql_url'    => (string) $github['graphql_url'],
            ],
        );
    },

    // -------------------------------------------------------------------------
    // Controllers
    // -------------------------------------------------------------------------
    AuthController::class => static fn(ContainerInterface $c): AuthController =>
        new AuthController($c->get(AuthService::class)),

    BurndownService::class => static fn(ContainerInterface $c): BurndownService =>
        new BurndownService(
            $c->get(BurndownRepository::class),
            $c->get(IssueRepository::class),
        ),

    EfficiencyService::class => static fn(ContainerInterface $c): EfficiencyService =>
        new EfficiencyService($c->get(IssueRepository::class)),

    // Placeholder controllers (Phase 3 will add real services)
    ProjectController::class => static fn(ContainerInterface $c): ProjectController =>
        new ProjectController(
            $c->get(ProjectRepository::class),
            $c->get(IssueRepository::class),
        ),
    IssueController::class => static fn(ContainerInterface $c): IssueController =>
        new IssueController($c->get(IssueRepository::class)),
    GitHubAccountController::class => static fn(ContainerInterface $c): GitHubAccountController =>
        new GitHubAccountController(
            $c->get(GitHubAccountRepository::class),
            $c->get(ProjectRepository::class),
            $c->get(AuditLogRepository::class),
            $c->get(CredentialVault::class),
            $c->get(GitHubClientFactoryInterface::class),
            $c->get(AccountConnectionTester::class),
            $c->get(GraphqlEndpointValidator::class),
            $c->get(EnvAccountImporter::class),
        ),
    EnvAccountImporter::class => static function (ContainerInterface $c): EnvAccountImporter {
        $github = $c->get('settings')['github'];

        return new EnvAccountImporter(
            $c->get(GitHubAccountRepository::class),
            $c->get(ProjectRepository::class),
            $c->get(AuditLogRepository::class),
            $c->get(CredentialVault::class),
            $c->get(GitHubClientFactoryInterface::class),
            $c->get(AccountConnectionTester::class),
            [
                'pat'            => (string) $github['pat'],
                'org'            => (string) $github['org'],
                'project_number' => (int) $github['project_number'],
                'graphql_url'    => (string) $github['graphql_url'],
            ],
        );
    },
    ProjectAdminController::class => static fn(ContainerInterface $c): ProjectAdminController =>
        new ProjectAdminController(
            $c->get(ProjectRepository::class),
            $c->get(GitHubAccountRepository::class),
            $c->get(AuditLogRepository::class),
            $c->get(GitHubClientFactoryInterface::class),
            $c->get(AccountConnectionTester::class),
            $c->get('settings')['github']['fields'],
        ),
    AdminController::class   => static fn(ContainerInterface $c): AdminController =>
        new AdminController($c->get(UserRepository::class)),

    MemberController::class => static fn(ContainerInterface $c): MemberController =>
        new MemberController($c->get(EfficiencyService::class)),

    BurndownController::class => static fn(ContainerInterface $c): BurndownController =>
        new BurndownController($c->get(BurndownService::class), $c->get(ProjectRepository::class)),

    SyncController::class => static fn(ContainerInterface $c): SyncController =>
        new SyncController(
            $c->get(SyncHistoryRepository::class),
            $c->get(SyncOrchestrator::class),
            $c->get(ProjectRepository::class),
        ),

    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------
    AuthMiddleware::class => static fn(ContainerInterface $c): AuthMiddleware =>
        new AuthMiddleware(
            $c->get(AuthService::class),
            $c->get(ResponseFactoryInterface::class),
        ),

    RequireJsonMiddleware::class => static fn(ContainerInterface $c): RequireJsonMiddleware =>
        new RequireJsonMiddleware($c->get(ResponseFactoryInterface::class)),
    AdminMiddleware::class => static fn(ContainerInterface $c): AdminMiddleware =>
        new AdminMiddleware($c->get(ResponseFactoryInterface::class)),

]);

return $builder->build();
