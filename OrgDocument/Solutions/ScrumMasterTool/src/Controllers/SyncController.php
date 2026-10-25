<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ProjectRepository;
use App\Repositories\SyncHistoryRepository;
use App\Services\SyncOrchestrator;
use App\Services\SyncOutcome;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * SyncController — HTTP handlers for GitHub sync endpoints.
 *
 * Routes (config/routes.php):
 *   GET  /api/sync/history                → history()      [auth required]
 *   POST /api/projects/{id}/sync          → syncProject()  [admin required]
 *   POST /api/sync/trigger                → trigger()      [admin; legacy alias: first tracked project]
 *
 * A web request syncs ONE project (shared-hosting time limit); syncing every project is the
 * cron job's task (cron/sync.php).
 */
final class SyncController
{
    public function __construct(
        private readonly SyncHistoryRepository $historyRepo,
        private readonly SyncOrchestrator      $orchestrator,
        private readonly ProjectRepository     $projects,
    ) {
    }

    /**
     * GET /api/sync/history[?project_id=N]
     *
     * Returns the last 20 sync history records, newest first. Pass `?project_id=N`
     * to restrict them to one project; without it, all projects (and failed runs
     * not tied to a project) are included.
     *
     * Response 200:
     * {
     *   "data": [ { "id": 1, "synced_at": "...", "status": "success", ... }, ... ]
     * }
     */
    public function history(Request $request, Response $response): Response
    {
        // project_id=0 means "all projects"
        $params    = $request->getQueryParams();
        $projectId = (int) ($params['project_id'] ?? 0);

        // Only the snapshot file name is exposed, not the server path
        $records = array_map(static function (array $row): array {
            if (!empty($row['snapshot_file'])) {
                $row['snapshot_file'] = basename((string) $row['snapshot_file']);
            }

            return $row;
        }, $this->historyRepo->findLatest($projectId, 20));

        $response->getBody()->write(
            json_encode(['data' => $records], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        return $response->withStatus(200);
    }

    /**
     * POST /api/projects/{id}/sync
     *
     * 200 { project_id, name, status: ok|partial, issues_added, issues_updated, unchanged, errors, ... }
     * 404 unknown project · 409 archived project, or an admin must act (status needs_attention)
     * 429 rate limited · 502 GitHub/transport failure
     */
    public function syncProject(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $project = $this->projects->findById($id);

        if ($project === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        if (!$project['is_tracked']) {
            return $this->json($response, ['error' => 'This project is archived (untracked). Track it again to sync.'], 409);
        }

        try {
            $outcome = $this->orchestrator->syncProject($id);
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        return $this->respond($response, $outcome);
    }

    /**
     * POST /api/sync/trigger — legacy alias: syncs the first tracked project
     * (or bootstraps from .env credentials on a fresh legacy install).
     */
    public function trigger(Request $request, Response $response): Response
    {
        try {
            $tracked = $this->projects->findTracked();
            $outcome = $tracked !== []
                ? $this->orchestrator->syncProject((int) $tracked[0]['id'])
                : ($this->orchestrator->runAll()[0] ?? null);
        } catch (\PDOException) {
            return $this->json($response, ['error' => 'Database error.'], 500);
        }

        if ($outcome === null) {
            return $this->json($response, ['error' => 'No project is configured yet. Add a GitHub account and track a project.'], 409);
        }

        return $this->respond($response, $outcome);
    }

    private function respond(Response $response, ?SyncOutcome $outcome): Response
    {
        if ($outcome === null) {
            return $this->json($response, ['error' => 'Project not found.'], 404);
        }

        if ($outcome->isSuccess()) {
            return $this->json($response, $outcome->toArray());
        }

        $status = match ($outcome->status) {
            'rate_limited'    => 429,
            'needs_attention' => 409,
            default           => 502,
        };

        return $this->json($response, ['error' => $outcome->message] + $outcome->toArray(), $status);
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
