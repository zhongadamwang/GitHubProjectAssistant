<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\CredentialConfigurationException;
use App\Exceptions\CredentialDecryptionException;
use App\Exceptions\GitHubApiException;
use App\Exceptions\RateLimitException;
use App\GraphQL\ResponseParser;
use App\Repositories\AuditLogRepository;
use App\Repositories\GitHubAccountRepository;
use App\Repositories\ProjectRepository;
use App\Services\AccountConnectionTester;
use App\Services\FieldMapping;
use App\Services\GitHubAccountClientInterface;
use App\Services\GitHubClientFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ProjectAdminController — admin-only management of tracked GitHub projects.
 *
 * Routes (config/routes.php — admin group):
 *   POST   /api/admin/projects                       → track()          track (or re-track) a project
 *   PUT    /api/admin/projects/{id}                  → update()         field mapping, tracked flag (name comes from GitHub)
 *   DELETE /api/admin/projects/{id}                  → untrack()        archive: stop syncing, keep data
 *   DELETE /api/admin/projects/{id}/data             → deleteData()     explicit, confirmed data removal
 *   POST   /api/admin/projects/{id}/validate-fields  → validateFields() check the mapped fields on GitHub
 *
 * Untracking never deletes data; deleting data requires the project to be archived first
 * and the project name as confirmation.
 */
final class ProjectAdminController
{
    /** Supported size (system limitation) — more tracked projects only produces a warning. */
    public const SUPPORTED_PROJECTS = 10;

    /**
     * @param array<string,string> $globalFieldNames  settings.github.fields (role → name)
     */
    public function __construct(
        private readonly ProjectRepository          $projects,
        private readonly GitHubAccountRepository    $accounts,
        private readonly AuditLogRepository         $audit,
        private readonly GitHubClientFactoryInterface $clients,
        private readonly AccountConnectionTester    $tester,
        private readonly array                      $globalFieldNames,
    ) {
    }

    /**
     * POST /api/admin/projects  { account_id, number }
     * 201 { project, warning? } · 200 when already tracked · 404 · 422 · 502
     */
    public function track(Request $request, Response $response): Response
    {
        $body      = (array) ($request->getParsedBody() ?? []);
        $accountId = (int) ($body['account_id'] ?? 0);
        $number    = (int) ($body['number'] ?? 0);

        if ($accountId < 1 || $number < 1) {
            return $this->json($response, ['errors' => ['account_id' => 'Account is required.', 'number' => 'Project number is required.']], 422);
        }

        $account = $this->accounts->findWithSecret($accountId);
        if ($account === null) {
            return $this->json($response, ['error' => 'GitHub account not found.'], 404);
        }

        $found = null;
        $error = $this->withClient($response, $account, function (GitHubAccountClientInterface $client) use ($account, $number, &$found): void {
            foreach ($client->listOwnerProjects((string) $account['owner_login']) as $candidate) {
                if ($candidate['number'] === $number) {
                    $found = $candidate;
                    break;
                }
            }
        });
        if ($error !== null) {
            return $error;
        }
        if ($found === null) {
            return $this->json($response, ['error' => 'This account has no project with that number.'], 404);
        }

        $existing = $this->projects->findByOwnerAndNumber((string) $account['owner_login'], $number);
        $already  = $existing !== null && $existing['is_tracked'];

        try {
            $id = $this->projects->track([
                'account_id'        => $accountId,
                'github_project_id' => $found['id'],
                'github_owner'      => (string) $account['owner_login'],
                'project_number'    => $number,
                'name'              => $found['title'],
            ]);
            $tracked = $this->projects->countTracked();
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        if (!$already) {
            $this->audit->append($this->actorId($request), 'project.tracked', 'project', $id, [
                'account_id' => $accountId, 'number' => $number, 'name' => $found['title'],
            ]);
        }

        $payload = ['project' => $this->projects->findById($id)];
        if ($tracked > self::SUPPORTED_PROJECTS) {
            $payload['warning'] = sprintf(
                'More than %d projects are tracked. The supported size is up to %d projects of about 1,000 issues each; '
                . 'syncs may exceed hosting limits or GitHub\'s hourly quota.',
                self::SUPPORTED_PROJECTS,
                self::SUPPORTED_PROJECTS,
            );
        }

        return $this->json($response, $payload, $already ? 200 : 201);
    }

    /**
     * PUT /api/admin/projects/{id}  { field_mapping?: {estimated, remaining, actual, iteration}|null, is_tracked?: bool }
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $project = $this->projects->findById($id);

        if ($project === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        $body   = (array) ($request->getParsedBody() ?? []);
        $errors = [];
        $mapping = null;

        if (array_key_exists('field_mapping', $body)) {
            if ($body['field_mapping'] === null) {
                $mapping = [];
            } elseif (is_array($body['field_mapping'])) {
                [$mapping, $mappingErrors] = FieldMapping::sanitize($body['field_mapping']);
                $errors += array_map(static fn(string $m): string => $m, $mappingErrors);
            } else {
                $errors['field_mapping'] = 'Field mapping must be an object.';
            }
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            if ($mapping !== null) {
                $this->projects->setFieldMapping($id, $mapping);
            }
            if (array_key_exists('is_tracked', $body)) {
                $this->projects->setTracked($id, (bool) $body['is_tracked']);
            }
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        $this->audit->append($this->actorId($request), 'project.updated', 'project', $id, [
            'fields' => array_values(array_intersect(['field_mapping', 'is_tracked'], array_keys($body))),
        ]);

        return $this->json($response, ['project' => $this->projects->findById($id)]);
    }

    /** DELETE /api/admin/projects/{id} → archive (stop syncing, keep data). */
    public function untrack(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        if ($this->projects->findById($id) === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        $this->projects->setTracked($id, false);
        $this->audit->append($this->actorId($request), 'project.untracked', 'project', $id);

        return $this->json($response, ['project' => $this->projects->findById($id)]);
    }

    /**
     * DELETE /api/admin/projects/{id}/data  { confirm: "<project name>" }
     * Only for archived projects. 204 · 409 still tracked · 422 wrong confirmation.
     */
    public function deleteData(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $project = $this->projects->findById($id);

        if ($project === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        if ($project['is_tracked']) {
            return $this->json($response, ['error' => 'Untrack the project before deleting its data.'], 409);
        }

        $body = (array) ($request->getParsedBody() ?? []);
        if (!isset($body['confirm']) || (string) $body['confirm'] !== (string) $project['name']) {
            return $this->json($response, ['errors' => ['confirm' => 'Type the project name to confirm deletion.']], 422);
        }

        $this->projects->deleteWithData($id);
        $this->audit->append($this->actorId($request), 'project.data_deleted', 'project', $id, ['name' => $project['name']]);

        return $response->withStatus(204);
    }

    /**
     * POST /api/admin/projects/{id}/validate-fields
     * → { mapping: {role: name}, fields: [ { role, name, expected_type, status: found|missing|wrong_type, data_type } ], ok: bool }
     */
    public function validateFields(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $project = $this->projects->findById($id);

        if ($project === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        $account = $project['account_id'] !== null ? $this->accounts->findWithSecret((int) $project['account_id']) : null;
        if ($account === null) {
            return $this->json($response, ['error' => 'This project is not linked to a GitHub account.'], 409);
        }

        $fields = [];
        $error  = $this->withClient($response, $account, function (GitHubAccountClientInterface $client) use ($account, $project, &$fields): void {
            $type = (string) ($account['owner_type'] ?? 'organization');
            $raw  = $client->query('FETCH_PROJECT_FIELDS', ['owner' => $project['github_owner'], 'number' => (int) $project['project_number']]);
            $node = $raw['data'][$type]['projectV2'] ?? $raw['data']['organization']['projectV2'] ?? $raw['data']['user']['projectV2'] ?? [];
            $fields = ResponseParser::parseProject((array) $node, (string) $project['github_owner'])->fields;
        });
        if ($error !== null) {
            return $error;
        }

        $mapping = FieldMapping::resolve($this->globalFieldNames, $project['field_mapping']);
        $results = FieldMapping::validate($fields, $mapping);
        $ok      = count(array_filter($results, static fn(array $r): bool => $r['status'] !== 'found')) === 0;

        $this->audit->append($this->actorId($request), 'project.fields_validated', 'project', $id, ['ok' => $ok]);

        return $this->json($response, ['mapping' => $mapping, 'fields' => $results, 'ok' => $ok]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Run $work with a GitHub client for the account; translate failures into responses
     * (and keep the account status current). Returns null on success.
     *
     * @param array<string,mixed> $account row with secret
     */
    private function withClient(Response $response, array $account, callable $work): ?Response
    {
        try {
            $work($this->clients->forAccount($account));

            return null;
        } catch (CredentialConfigurationException) {
            return $this->json($response, ['error' => 'APP_KEY is not configured on the server.'], 503);
        } catch (CredentialDecryptionException) {
            $this->accounts->updateStatus((int) $account['id'], 'needs_reentry', 'The stored token cannot be decrypted — enter the token again.');

            return $this->json($response, ['error' => 'The account token cannot be decrypted — enter the token again.'], 409);
        } catch (GitHubApiException | RateLimitException $e) {
            $result = $this->tester->classify($e);
            $this->accounts->updateStatus((int) $account['id'], $result['status'], $result['message'], $result['verified_at']);

            return $this->json($response, ['error' => $result['message'], 'status' => $result['status']], 502);
        } catch (\RuntimeException) {
            return $this->json($response, ['error' => 'This account has no access token.'], 409);
        }
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
