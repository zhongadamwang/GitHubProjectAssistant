<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use PDO;

/**
 * ProjectRepository — all database access for the `projects` table.
 *
 * Columns mapped from the GitHub Projects v2 API response:
 *   github_project_id  ← Project::$githubId
 *   github_owner       ← Project::$owner
 *   project_number     ← Project::$number
 *   name               ← Project::$title
 *   sync_timestamp     ← set to NOW() on every upsert
 *
 * `github_repo` is not available from Projects v2 (owner-level projects are
 * not scoped to a single repo); stored as empty string.
 */
final class ProjectRepository
{
    private const COLUMNS = '`id`, `account_id`, `github_project_id`, `github_owner`, `project_number`,
                    `name`, `current_iteration`, `sync_timestamp`, `created_at`,
                    `is_tracked`, `archived_at`, `field_mapping`, `last_sync_status`, `last_sync_at`';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Insert or update the project row from GitHub data.
     * Sets `sync_timestamp` to the current UTC datetime on every call.
     */
    public function upsertFromGitHub(Project $project): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `projects`
                 (`github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`, `sync_timestamp`)
             VALUES
                 (:github_project_id, :github_owner, :github_repo, :project_number, :name, :sync_timestamp)
             ON DUPLICATE KEY UPDATE
                 `github_owner`    = VALUES(`github_owner`),
                 `project_number`  = VALUES(`project_number`),
                 `name`            = VALUES(`name`),
                 `sync_timestamp`  = VALUES(`sync_timestamp`)'
        );

        $stmt->execute([
            'github_project_id' => $project->githubId,
            'github_owner'      => $project->owner,
            'github_repo'       => '',          // Projects v2 are owner-scoped, not repo-scoped
            'project_number'    => $project->number,
            'name'              => $project->title,
            'sync_timestamp'    => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Return raw DB row for a project by its GitHub node ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findByGitHubId(string $githubId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `projects` WHERE `github_project_id` = ? LIMIT 1'
        );
        $stmt->execute([$githubId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Return the local auto-increment `id` for a GitHub project node ID,
     * or null when not yet persisted.
     */
    public function getLocalId(string $githubId): ?int
    {
        $row = $this->findByGitHubId($githubId);
        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * Return all project rows ordered by sync_timestamp DESC (most recently
     * synced first).  Used by ProjectController::listProjects().
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT ' . self::COLUMNS . '
               FROM `projects`
              ORDER BY `sync_timestamp` DESC'
        );

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * Return a single project row by its local auto-increment ID, or null.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . '
               FROM `projects`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $this->hydrate($row) : null;
    }

    // -------------------------------------------------------------------------
    // Tracking (REQ-ADD-003)
    // -------------------------------------------------------------------------

    /**
     * Projects for the API, with their account's label/status.
     * Archived (untracked) projects are only included on request.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findForApi(bool $includeArchived = false): array
    {
        $sql = 'SELECT p.`id`, p.`account_id`, p.`github_project_id`, p.`github_owner`, p.`project_number`,
                       p.`name`, p.`current_iteration`, p.`sync_timestamp`, p.`created_at`,
                       p.`is_tracked`, p.`archived_at`, p.`field_mapping`, p.`last_sync_status`, p.`last_sync_at`,
                       a.`label` AS account_label, a.`status` AS account_status
                  FROM `projects` p
                  LEFT JOIN `github_accounts` a ON a.`id` = p.`account_id`'
            . ($includeArchived ? '' : ' WHERE p.`is_tracked` = 1')
            . ' ORDER BY p.`is_tracked` DESC, p.`name` ASC, p.`id` ASC';

        return array_map([$this, 'hydrate'], $this->pdo->query($sql)->fetchAll());
    }

    /**
     * Tracked (active) projects in a stable order — what the cron job syncs.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findTracked(): array
    {
        return array_map(
            [$this, 'hydrate'],
            $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM `projects` WHERE `is_tracked` = 1 ORDER BY `id` ASC')->fetchAll()
        );
    }

    public function countTracked(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM `projects` WHERE `is_tracked` = 1')->fetchColumn();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByOwnerAndNumber(string $owner, int $number): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM `projects` WHERE `github_owner` = ? AND `project_number` = ? LIMIT 1'
        );
        $stmt->execute([$owner, $number]);
        $row = $stmt->fetch();

        return $row !== false ? $this->hydrate($row) : null;
    }

    /**
     * Start tracking a GitHub project (creates the row, or re-activates an archived one).
     *
     * @param array{account_id:int, github_project_id:string, github_owner:string,
     *              project_number:int, name:string} $data
     * @return int local project id
     */
    public function track(array $data): int
    {
        $this->pdo->prepare(
            'INSERT INTO `projects`
                 (`account_id`, `github_project_id`, `github_owner`, `github_repo`, `project_number`, `name`, `is_tracked`)
             VALUES
                 (:account_id, :github_project_id, :github_owner, \'\', :project_number, :name, 1)
             ON DUPLICATE KEY UPDATE
                 `account_id`     = VALUES(`account_id`),
                 `github_owner`   = VALUES(`github_owner`),
                 `project_number` = VALUES(`project_number`),
                 `name`           = VALUES(`name`),
                 `is_tracked`     = 1,
                 `archived_at`    = NULL'
        )->execute([
            'account_id'        => $data['account_id'],
            'github_project_id' => $data['github_project_id'],
            'github_owner'      => $data['github_owner'],
            'project_number'    => $data['project_number'],
            'name'              => $data['name'],
        ]);

        return (int) $this->getLocalId($data['github_project_id']);
    }

    /**
     * Track or untrack (archive) a project. Data is kept either way.
     */
    public function setTracked(int $id, bool $tracked): void
    {
        $this->pdo->prepare(
            'UPDATE `projects` SET `is_tracked` = :t, `archived_at` = ' . ($tracked ? 'NULL' : 'NOW()') . ' WHERE `id` = :id'
        )->execute(['t' => $tracked ? 1 : 0, 'id' => $id]);
    }

    /**
     * Per-project field-name overrides (null clears them → global defaults).
     *
     * @param array<string,string>|null $mapping keys: estimated, remaining, actual, iteration
     */
    public function setFieldMapping(int $id, ?array $mapping): void
    {
        $this->pdo->prepare('UPDATE `projects` SET `field_mapping` = ? WHERE `id` = ?')->execute([
            $mapping === null || $mapping === [] ? null : json_encode($mapping, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $id,
        ]);
    }

    public function setName(int $id, string $name): void
    {
        $this->pdo->prepare('UPDATE `projects` SET `name` = ? WHERE `id` = ?')->execute([$name, $id]);
    }

    public function linkAccount(int $id, ?int $accountId): void
    {
        $this->pdo->prepare('UPDATE `projects` SET `account_id` = ? WHERE `id` = ?')->execute([$accountId, $id]);
    }

    /**
     * Record the outcome of the latest sync attempt (ok | failed | partial | rate_limited | needs_attention).
     */
    public function recordSyncStatus(int $id, string $status): void
    {
        $this->pdo->prepare(
            'UPDATE `projects` SET `last_sync_status` = ?, `last_sync_at` = UTC_TIMESTAMP() WHERE `id` = ?'
        )->execute([$status, $id]);
    }

    /**
     * Permanently delete a project and (through FK cascades) its issues, snapshots and iterations.
     */
    public function deleteWithData(int $id): void
    {
        $this->pdo->prepare('DELETE FROM `projects` WHERE `id` = ?')->execute([$id]);
    }

    /**
     * Decode JSON / boolean columns.
     *
     * @param  array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate(array $row): array
    {
        if (array_key_exists('field_mapping', $row)) {
            $decoded = $row['field_mapping'] !== null ? json_decode((string) $row['field_mapping'], true) : null;
            $row['field_mapping'] = is_array($decoded) ? $decoded : null;
        }
        if (array_key_exists('is_tracked', $row)) {
            $row['is_tracked'] = (bool) $row['is_tracked'];
        }

        return $row;
    }

    /**
     * Replace the stored iteration (sprint) configuration of a project.
     *
     * @param array<int,array{title?:string,startDate?:string|null,duration?:int|null,completed?:bool}> $iterations
     */
    public function syncIterations(int $projectId, array $iterations): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `project_iterations` (`project_id`, `title`, `start_date`, `duration_days`, `is_completed`)
             VALUES (:project_id, :title, :start_date, :duration_days, :is_completed)
             ON DUPLICATE KEY UPDATE
                 `start_date`    = VALUES(`start_date`),
                 `duration_days` = VALUES(`duration_days`),
                 `is_completed`  = VALUES(`is_completed`)'
        );

        foreach ($iterations as $it) {
            $title = trim((string) ($it['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $stmt->execute([
                'project_id'    => $projectId,
                'title'         => $title,
                'start_date'    => $it['startDate'] ?? null,
                'duration_days' => isset($it['duration']) ? (int) $it['duration'] : null,
                'is_completed'  => !empty($it['completed']) ? 1 : 0,
            ]);
        }
    }

    /**
     * Record which iteration is running now (null clears it).
     */
    public function updateCurrentIteration(int $projectId, ?string $title): void
    {
        $stmt = $this->pdo->prepare('UPDATE `projects` SET `current_iteration` = ? WHERE `id` = ?');
        $stmt->execute([$title, $projectId]);
    }

    /**
     * List every iteration known for a project, newest first.
     *
     * Union of the GitHub iteration configuration (project_iterations), iteration
     * names found on issues, and names present in burndown_daily — so sprints
     * appear even if GitHub no longer lists them or they have no issues yet.
     *
     * @return array<int,array{
     *   name: string, start_date: ?string, duration_days: ?int,
     *   is_completed: bool, has_data: bool
     * }>
     */
    public function findIterations(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.`name`,
                    pi.`start_date`,
                    pi.`duration_days`,
                    COALESCE(pi.`is_completed`, 0) AS is_completed,
                    EXISTS (SELECT 1 FROM `burndown_daily` b
                             WHERE b.`project_id` = :p4 AND b.`iteration` = t.`name`) AS has_data
               FROM (
                    SELECT `title` AS `name` FROM `project_iterations` WHERE `project_id` = :p1
                    UNION
                    SELECT `iteration` FROM `issues` WHERE `project_id` = :p2 AND `iteration` IS NOT NULL
                    UNION
                    SELECT `iteration` FROM `burndown_daily` WHERE `project_id` = :p3
               ) t
               LEFT JOIN `project_iterations` pi
                      ON pi.`project_id` = :p5 AND pi.`title` = t.`name`
              ORDER BY pi.`start_date` IS NULL, pi.`start_date` DESC, t.`name` DESC'
        );
        $stmt->execute(['p1' => $projectId, 'p2' => $projectId, 'p3' => $projectId, 'p4' => $projectId, 'p5' => $projectId]);

        return array_map(static fn(array $r): array => [
            'name'          => (string) $r['name'],
            'start_date'    => $r['start_date'] !== null ? (string) $r['start_date'] : null,
            'duration_days' => $r['duration_days'] !== null ? (int) $r['duration_days'] : null,
            'is_completed'  => (bool) $r['is_completed'],
            'has_data'      => (bool) $r['has_data'],
        ], $stmt->fetchAll());
    }
}
