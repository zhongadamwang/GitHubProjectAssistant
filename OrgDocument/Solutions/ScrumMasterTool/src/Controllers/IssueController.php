<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\IssueRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * IssueController — HTTP handler for the paginated, read-only issue list.
 * Hours are synced from GitHub; there is no endpoint to edit them.
 *
 * Routes (config/routes.php — auth group):
 *   GET /api/projects/{id}/issues   → getIssues()
 */
final class IssueController
{
    private const PAGE_SIZES = [10, 25, 50, 100];

    private const DEFAULT_PAGE_SIZE = 25;

    public function __construct(
        private readonly IssueRepository $issueRepo,
    ) {
    }

    /**
     * GET /api/projects/{id}/issues
     *
     * Query params (all optional):
     *   assignee, iteration, status=open|closed   — filters
     *   sort  = id|title|assignee|status|iteration|estimated_time|remaining_time|
     *           actual_time|github_updated_at      (default github_updated_at)
     *   dir   = asc|desc                           (default desc)
     *   page  = 1..N                               (default 1; beyond the last page → last page)
     *   page_size = 10|25|50|100                   (default 25)
     *
     * Response 200:
     * {
     *   "issues": [ {...}, ... ],           // current page
     *   "total": int,                       // issues matching the filters (all pages)
     *   "meta": { "page", "page_size", "total", "total_pages" },
     *   "totals": { "estimated", "remaining", "actual" },   // over all filtered issues
     *   "assignees": [ "alice", ... ]       // distinct assignees of the project
     * }
     *
     * Response 400 on an invalid page / page_size / sort / dir.
     */
    public function getIssues(Request $request, Response $response, array $args): Response
    {
        $projectId = (int) ($args['id'] ?? 0);
        $params    = $request->getQueryParams();

        $filters = [];
        foreach (['assignee', 'iteration', 'status'] as $key) {
            if (isset($params[$key]) && trim((string) $params[$key]) !== '') {
                $filters[$key] = trim((string) $params[$key]);
            }
        }

        $page     = $this->intParam($params, 'page', 1);
        $pageSize = $this->intParam($params, 'page_size', self::DEFAULT_PAGE_SIZE);
        $sort     = isset($params['sort']) ? (string) $params['sort'] : 'github_updated_at';
        $dir      = strtolower(isset($params['dir']) ? (string) $params['dir'] : 'desc');

        if (
            $page === null || $page < 1
            || $pageSize === null || !in_array($pageSize, self::PAGE_SIZES, true)
            || !in_array($sort, IssueRepository::SORT_COLUMNS, true)
            || !in_array($dir, ['asc', 'desc'], true)
        ) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid pagination parameters. page_size must be one of '
                    . implode(', ', self::PAGE_SIZES) . '.',
            ], JSON_THROW_ON_ERROR));
            return $response->withStatus(400);
        }

        try {
            $summary    = $this->issueRepo->summarizeByProject($projectId, $filters);
            $total      = $summary['total'];
            $totalPages = max(1, (int) ceil($total / $pageSize));
            $page       = min($page, $totalPages);

            $issues    = $this->issueRepo->findPageByProject(
                $projectId,
                $filters,
                $sort,
                $dir,
                $pageSize,
                ($page - 1) * $pageSize,
            );
            $assignees = $this->issueRepo->distinctAssignees($projectId);
        } catch (\PDOException) {
            $response->getBody()->write(json_encode(['error' => 'Database error.'], JSON_THROW_ON_ERROR));
            return $response->withStatus(500);
        }

        // Decode JSON labels column for each row
        $issues = array_map(static function (array $row): array {
            if (isset($row['labels']) && is_string($row['labels'])) {
                $decoded = json_decode($row['labels'], true);
                $row['labels'] = is_array($decoded) ? $decoded : [];
            }
            return $row;
        }, $issues);

        $response->getBody()->write(
            json_encode(
                [
                    'issues'    => $issues,
                    'total'     => $total,
                    'meta'      => [
                        'page'        => $page,
                        'page_size'   => $pageSize,
                        'total'       => $total,
                        'total_pages' => $totalPages,
                    ],
                    'totals'    => $summary['totals'],
                    'assignees' => $assignees,
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            )
        );

        return $response->withStatus(200);
    }

    /**
     * Read an optional integer query param; null when present but not an integer.
     *
     * @param array<string,mixed> $params
     */
    private function intParam(array $params, string $key, int $default): ?int
    {
        if (!isset($params[$key]) || $params[$key] === '') {
            return $default;
        }

        $value = filter_var($params[$key], FILTER_VALIDATE_INT);

        return $value === false ? null : $value;
    }
}
