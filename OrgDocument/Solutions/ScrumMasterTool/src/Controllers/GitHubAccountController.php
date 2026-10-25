<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\CredentialConfigurationException;
use App\Exceptions\CredentialDecryptionException;
use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\Repositories\AuditLogRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;
use App\Services\AccountConnectionTester;
use App\Services\CredentialVault;
use App\Services\EnvAccountImporter;
use App\Services\GitHubClientFactoryInterface;
use App\Services\GraphqlEndpointValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GitHubAccountController — admin-only management of GitHub accounts and their tokens.
 *
 * Routes (config/routes.php — admin group):
 *   GET    /api/admin/github-accounts                 → list()
 *   POST   /api/admin/github-accounts                 → create()
 *   PUT    /api/admin/github-accounts/{id}            → update()
 *   DELETE /api/admin/github-accounts/{id}            → delete()
 *   POST   /api/admin/github-accounts/{id}/test       → test()
 *   GET    /api/admin/github-accounts/{id}/projects   → discover()
 *   POST   /api/admin/github-accounts/import-env      → importEnv()
 *   GET    /api/admin/audit-log                       → auditLog()
 *
 * Security: the access token is accepted on create/update only, stored encrypted
 * (CredentialVault) and NEVER returned — responses carry `has_token`, `token_hint`
 * (last four characters) and the status. Every change is audited without secrets.
 * Test and discovery calls are limited to 10 per minute per admin.
 */
final class GitHubAccountController
{
    private const RATE_LIMIT_PER_MINUTE = 10;

    public function __construct(
        private readonly GitHubAccountRepository  $accounts,
        private readonly ProjectRepository        $projects,
        private readonly AuditLogRepository       $audit,
        private readonly CredentialVault          $vault,
        private readonly GitHubClientFactoryInterface $clients,
        private readonly AccountConnectionTester  $tester,
        private readonly GraphqlEndpointValidator $endpointValidator,
        private readonly EnvAccountImporter       $importer,
    ) {
    }

    /** GET /api/admin/github-accounts → { "accounts": [ ... ] } */
    public function list(Request $request, Response $response): Response
    {
        return $this->json($response, ['accounts' => $this->accounts->findAllPublic()]);
    }

    /**
     * POST /api/admin/github-accounts
     * Body: { label, owner_login, token, graphql_url? }
     * 201 { account } · 409 duplicate owner/endpoint · 422 validation · 503 APP_KEY missing
     */
    public function create(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        $errors = $this->validate($body, true);
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        if (!$this->vault->isConfigured()) {
            return $this->json($response, ['error' => 'APP_KEY is not configured on the server; tokens cannot be stored.'], 503);
        }

        $label = trim((string) $body['label']);
        $owner = trim((string) $body['owner_login']);
        $token = trim((string) $body['token']);
        $url   = $this->endpointFrom($body);

        if ($this->accounts->findByOwner($owner, $url) !== null) {
            return $this->json($response, ['error' => 'An account for this owner and endpoint already exists.'], 409);
        }

        try {
            $ownerType = $this->detectOwnerType($token, $url, $owner);
            $id        = $this->accounts->create([
                'label' => $label, 'owner_login' => $owner, 'owner_type' => $ownerType,
                'graphql_url' => $url, 'created_by' => $this->actorId($request),
            ]);
            $this->storeToken($id, $token);
            $this->runTest($id);
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        $this->audit->append($this->actorId($request), 'account.created', 'github_account', $id, [
            'label' => $label, 'owner' => $owner, 'owner_type' => $ownerType, 'endpoint' => $url,
        ]);

        return $this->json($response, ['account' => $this->accounts->findPublicById($id)], 201);
    }

    /**
     * PUT /api/admin/github-accounts/{id}
     * Body: { label?, graphql_url?, token? } — a token replaces the stored one (replace-only).
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $account = $this->accounts->findPublicById($id);

        if ($account === null) {
            return $this->json($response, ['error' => 'GitHub account not found.'], 404);
        }

        $body   = (array) ($request->getParsedBody() ?? []);
        $errors = $this->validate($body, false);
        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $newToken = isset($body['token']) ? trim((string) $body['token']) : '';
        if ($newToken !== '' && !$this->vault->isConfigured()) {
            return $this->json($response, ['error' => 'APP_KEY is not configured on the server; tokens cannot be stored.'], 503);
        }

        $changes = [];
        if (isset($body['label'])) {
            $changes['label'] = trim((string) $body['label']);
        }
        $urlChanged = false;
        if (isset($body['graphql_url']) && trim((string) $body['graphql_url']) !== '') {
            $changes['graphql_url'] = trim((string) $body['graphql_url']);
            $urlChanged             = $changes['graphql_url'] !== $account['graphql_url'];
        }

        try {
            $this->accounts->update($id, $changes);

            if ($newToken !== '') {
                $this->storeToken($id, $newToken);
            }
            if ($newToken !== '' || $urlChanged) {
                $this->runTest($id);
            }
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        $this->audit->append($this->actorId($request), $newToken !== '' ? 'account.token_replaced' : 'account.updated', 'github_account', $id, [
            'fields' => array_keys($changes),
        ]);

        return $this->json($response, ['account' => $this->accounts->findPublicById($id)]);
    }

    /** DELETE /api/admin/github-accounts/{id} → 204 · 409 while projects reference the account */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $account = $this->accounts->findPublicById($id);

        if ($account === null) {
            return $this->json($response, ['error' => 'GitHub account not found.'], 404);
        }

        if ($this->accounts->countProjects($id) > 0) {
            return $this->json($response, ['error' => 'This account still has projects. Untrack and delete their data first.'], 409);
        }

        $this->accounts->delete($id);
        $this->audit->append($this->actorId($request), 'account.deleted', 'github_account', $id, [
            'label' => $account['label'], 'owner' => $account['owner_login'],
        ]);

        return $response->withStatus(204);
    }

    /** POST /api/admin/github-accounts/{id}/test → { account } with the refreshed status · 429 when rate limited */
    public function test(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($this->accounts->findPublicById($id) === null) {
            return $this->json($response, ['error' => 'GitHub account not found.'], 404);
        }

        if ($this->rateLimited($request, 'account.tested')) {
            return $this->json($response, ['error' => 'Too many connection tests — wait a minute.'], 429);
        }

        try {
            $result = $this->runTest($id);
        } catch (CredentialConfigurationException) {
            return $this->json($response, ['error' => 'APP_KEY is not configured on the server.'], 503);
        }

        $this->audit->append($this->actorId($request), 'account.tested', 'github_account', $id, ['status' => $result['status']]);

        return $this->json($response, ['account' => $this->accounts->findPublicById($id), 'result' => ['status' => $result['status'], 'message' => $result['message']]]);
    }

    /**
     * GET /api/admin/github-accounts/{id}/projects
     * → { "projects": [ { id, number, title, closed, url, description, tracked, archived, project_id } ] }
     */
    public function discover(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $account = $this->accounts->findWithSecret($id);

        if ($account === null) {
            return $this->json($response, ['error' => 'GitHub account not found.'], 404);
        }

        if ($this->rateLimited($request, 'account.discovered')) {
            return $this->json($response, ['error' => 'Too many requests — wait a minute.'], 429);
        }

        try {
            $client   = $this->clients->forAccount($account);
            $projects = $client->listOwnerProjects((string) $account['owner_login']);
        } catch (CredentialConfigurationException) {
            return $this->json($response, ['error' => 'APP_KEY is not configured on the server.'], 503);
        } catch (CredentialDecryptionException) {
            $this->accounts->updateStatus($id, 'needs_reentry', 'The stored token cannot be decrypted — enter the token again.');

            return $this->json($response, ['error' => 'The stored token cannot be decrypted — enter the token again.'], 409);
        } catch (\RuntimeException $e) {
            if ($e instanceof GitHubApiException || $e instanceof RateLimitException) {
                $result = $this->tester->classify($e);
                $this->accounts->updateStatus($id, $result['status'], $result['message'], $result['verified_at']);

                return $this->json($response, ['error' => $result['message'], 'status' => $result['status']], 502);
            }

            return $this->json($response, ['error' => 'This account has no access token.'], 409);
        }

        $known = [];
        foreach ($this->projects->findForApi(true) as $row) {
            $known[$row['github_project_id']] = $row;
        }

        $this->audit->append($this->actorId($request), 'account.discovered', 'github_account', $id, ['count' => count($projects)]);

        return $this->json($response, ['projects' => array_map(static function (array $p) use ($known): array {
            $local = $known[$p['id']] ?? null;

            return [
                'id'          => $p['id'],
                'number'      => $p['number'],
                'title'       => $p['title'],
                'closed'      => $p['closed'],
                'url'         => $p['url'],
                'description' => $p['shortDescription'],
                'tracked'     => $local !== null && $local['is_tracked'],
                'archived'    => $local !== null && !$local['is_tracked'],
                'project_id'  => $local !== null ? (int) $local['id'] : null,
            ];
        }, $projects)]);
    }

    /**
     * POST /api/admin/github-accounts/import-env
     * Moves the legacy .env GitHub settings into an account + tracked project (idempotent).
     * 201 imported · 200 already imported · 422 legacy settings missing · 503 APP_KEY missing
     */
    public function importEnv(Request $request, Response $response): Response
    {
        $result = $this->importer->import($this->actorId($request));

        $status = match ($result['status']) {
            'imported'        => 201,
            'already_imported' => 200,
            'not_configured'  => 422,
            default           => 503,
        };

        $payload = $result + ['account' => $result['account_id'] !== null ? $this->accounts->findPublicById($result['account_id']) : null];

        return $this->json($response, $payload, $status);
    }

    /** GET /api/admin/audit-log → { "entries": [ ... ] } (latest 100, no secrets) */
    public function auditLog(Request $request, Response $response): Response
    {
        return $this->json($response, ['entries' => $this->audit->latest(100)]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Encrypt and store a token (also stores the display hint).
     */
    private function storeToken(int $id, string $token): void
    {
        $enc = $this->vault->encrypt($token);
        $this->accounts->saveToken($id, $enc['ciphertext'], $enc['nonce'], CredentialVault::hint($token));
    }

    /**
     * Test a stored account and persist the status (and the detected owner type).
     *
     * @return array{status: string, message: string, verified_at: string, expires_at: ?string, viewer: ?string, projects: ?int}
     *
     * @throws CredentialConfigurationException
     */
    private function runTest(int $id): array
    {
        $account = $this->accounts->findWithSecret($id);

        try {
            $client = $this->clients->forAccount((array) $account);
        } catch (CredentialConfigurationException $e) {
            throw $e;
        } catch (CredentialDecryptionException) {
            $this->accounts->updateStatus($id, 'needs_reentry', 'The stored token cannot be decrypted — enter the token again.');

            return ['status' => 'needs_reentry', 'message' => 'The stored token cannot be decrypted — enter the token again.', 'verified_at' => gmdate('Y-m-d H:i:s'), 'expires_at' => null, 'viewer' => null, 'projects' => null];
        } catch (\RuntimeException) {
            $this->accounts->updateStatus($id, 'unverified', 'No access token stored.');

            return ['status' => 'unverified', 'message' => 'No access token stored.', 'verified_at' => gmdate('Y-m-d H:i:s'), 'expires_at' => null, 'viewer' => null, 'projects' => null];
        }

        $result = $this->tester->test($client, (string) $account['owner_login']);
        $this->accounts->updateStatus($id, $result['status'], $result['message'], $result['verified_at'], $result['expires_at']);

        return $result;
    }

    /**
     * Ask GitHub whether the login is an organization or a user ('organization' when unknown).
     */
    private function detectOwnerType(string $token, string $url, string $owner): string
    {
        try {
            return $this->clients->forToken($token, $url)->detectOwnerType($owner) === 'User' ? 'user' : 'organization';
        } catch (\Throwable) {
            return 'organization';
        }
    }

    /**
     * @param  array<string,mixed> $body
     * @return array<string,string> field → message
     */
    private function validate(array $body, bool $creating): array
    {
        $errors = [];

        if ($creating || array_key_exists('label', $body)) {
            $label = trim((string) ($body['label'] ?? ''));
            if ($label === '' || mb_strlen($label) > 100) {
                $errors['label'] = 'Label is required (max 100 characters).';
            }
        }

        if ($creating) {
            $owner = trim((string) ($body['owner_login'] ?? ''));
            if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $owner) !== 1) {
                $errors['owner_login'] = 'Enter the GitHub organization or user login (letters, digits, hyphens).';
            }
        }

        if ($creating || (isset($body['token']) && trim((string) $body['token']) !== '')) {
            $token = trim((string) ($body['token'] ?? ''));
            if (strlen($token) < 20 || strlen($token) > 255 || preg_match('/^[A-Za-z0-9_\-]+$/', $token) !== 1) {
                $errors['token'] = 'Enter a valid GitHub access token.';
            }
        }

        if (isset($body['graphql_url']) && trim((string) $body['graphql_url']) !== '') {
            $problem = $this->endpointValidator->validate((string) $body['graphql_url']);
            if ($problem !== null) {
                $errors['graphql_url'] = $problem;
            }
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $body
     */
    private function endpointFrom(array $body): string
    {
        $url = isset($body['graphql_url']) ? trim((string) $body['graphql_url']) : '';

        return $url !== '' ? $url : GraphqlEndpointValidator::DEFAULT_ENDPOINT;
    }

    private function rateLimited(Request $request, string $action): bool
    {
        return $this->audit->countRecent($this->actorId($request), $action, 60) >= self::RATE_LIMIT_PER_MINUTE;
    }

    private function actorId(Request $request): ?int
    {
        $user = $request->getAttribute('auth_user');

        return $user !== null ? (int) $user->id : null;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status);
    }
}
