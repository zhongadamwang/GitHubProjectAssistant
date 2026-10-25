<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * AuditLogRepository — append-only log of administrative changes.
 *
 * Detail payloads must never contain secrets (tokens, passwords, keys);
 * callers pass identifiers and non-sensitive attributes only.
 */
class AuditLogRepository
{
    /** Keys that are dropped from any detail payload as a safety net. */
    private const FORBIDDEN_KEYS = ['token', 'pat', 'password', 'secret', 'ciphertext', 'nonce', 'key', 'authorization'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array<string,mixed> $detail  Non-sensitive context
     */
    public function append(?int $actorUserId, string $action, string $entity, ?int $entityId = null, array $detail = []): void
    {
        $safe = $this->stripSecrets($detail);

        $this->pdo->prepare(
            'INSERT INTO `admin_audit_log` (`actor_user_id`, `action`, `entity`, `entity_id`, `detail`)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $actorUserId,
            $action,
            $entity,
            $entityId,
            $safe === [] ? null : json_encode($safe, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Number of entries by an actor for an action within the last $seconds
     * (used as a simple DB-backed rate limiter).
     */
    public function countRecent(?int $actorUserId, string $action, int $seconds): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `admin_audit_log`
              WHERE `action` = :action AND `actor_user_id` <=> :actor
                AND `created_at` >= (NOW() - INTERVAL :secs SECOND)'
        );
        $stmt->bindValue('action', $action);
        $stmt->bindValue('actor', $actorUserId, $actorUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('secs', $seconds, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Latest entries first, with the actor's display name.
     *
     * @return array<int,array<string,mixed>>
     */
    public function latest(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.`id`, l.`actor_user_id`, u.`display_name` AS actor_name, l.`action`, l.`entity`,
                    l.`entity_id`, l.`detail`, l.`created_at`
               FROM `admin_audit_log` l
               LEFT JOIN `users` u ON u.`id` = l.`actor_user_id`
              ORDER BY l.`id` DESC
              LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $row['detail'] = $row['detail'] !== null ? json_decode((string) $row['detail'], true) : null;

            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * @param  array<string,mixed> $detail
     * @return array<string,mixed>
     */
    private function stripSecrets(array $detail): array
    {
        $clean = [];

        foreach ($detail as $key => $value) {
            if (in_array(strtolower((string) $key), self::FORBIDDEN_KEYS, true)) {
                continue;
            }
            $clean[$key] = is_array($value) ? $this->stripSecrets($value) : $value;
        }

        return $clean;
    }
}
