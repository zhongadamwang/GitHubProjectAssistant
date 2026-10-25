<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * GitHubAccountRepository — all database access for `github_accounts`.
 *
 * The access token is only ever handled as opaque ciphertext here (encryption
 * is done by CredentialVault). Read methods used by the API return the
 * "public" shape which never contains the ciphertext or nonce; only
 * findWithSecret() (used by the GitHub client factory) does.
 */
class GitHubAccountRepository
{
    /** Columns safe to expose to API consumers. */
    private const PUBLIC_COLUMNS = 'a.`id`, a.`label`, a.`owner_login`, a.`owner_type`, a.`graphql_url`,
            a.`token_hint`, (a.`token_ciphertext` IS NOT NULL) AS has_token,
            a.`status`, a.`status_message`, a.`last_verified_at`, a.`token_expires_at`,
            a.`created_by`, a.`created_at`, a.`updated_at`,
            (SELECT COUNT(*) FROM `projects` p WHERE p.`account_id` = a.`id` AND p.`is_tracked` = 1) AS tracked_projects';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array{label:string, owner_login:string, owner_type?:string, graphql_url?:string,
     *              created_by?:int|null} $data
     * @return int new account id
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `github_accounts` (`label`, `owner_login`, `owner_type`, `graphql_url`, `created_by`)
             VALUES (:label, :owner_login, :owner_type, :graphql_url, :created_by)'
        );
        $stmt->execute([
            'label'       => $data['label'],
            'owner_login' => $data['owner_login'],
            'owner_type'  => $data['owner_type'] ?? 'organization',
            'graphql_url' => $data['graphql_url'] ?? 'https://api.github.com/graphql',
            'created_by'  => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update the non-secret fields (label, graphql_url, owner_type).
     *
     * @param array<string,string> $data
     */
    public function update(int $id, array $data): void
    {
        $allowed = ['label', 'graphql_url', 'owner_type'];
        $sets    = [];
        $params  = ['id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]        = "`{$col}` = :{$col}";
                $params[$col]  = $data[$col];
            }
        }

        if ($sets === []) {
            return;
        }

        $this->pdo->prepare('UPDATE `github_accounts` SET ' . implode(', ', $sets) . ' WHERE `id` = :id')
            ->execute($params);
    }

    /**
     * Store (or replace) the encrypted token. A replaced token resets the status
     * to 'unverified' because it has not been tested yet.
     */
    public function saveToken(int $id, string $ciphertext, string $nonce, string $hint): void
    {
        $this->pdo->prepare(
            "UPDATE `github_accounts`
                SET `token_ciphertext` = :c, `token_nonce` = :n, `token_hint` = :h,
                    `status` = 'unverified', `status_message` = NULL,
                    `last_verified_at` = NULL, `token_expires_at` = NULL
              WHERE `id` = :id"
        )->execute(['c' => $ciphertext, 'n' => $nonce, 'h' => $hint, 'id' => $id]);
    }

    /**
     * Record the result of a connection test or an authentication failure.
     */
    public function updateStatus(
        int     $id,
        string  $status,
        ?string $message = null,
        ?string $verifiedAt = null,
        ?string $expiresAt = null,
    ): void {
        $this->pdo->prepare(
            'UPDATE `github_accounts`
                SET `status` = :status, `status_message` = :message,
                    `last_verified_at` = COALESCE(:verified, `last_verified_at`),
                    `token_expires_at` = COALESCE(:expires, `token_expires_at`)
              WHERE `id` = :id'
        )->execute([
            'status'   => $status,
            'message'  => $message !== null ? mb_substr($message, 0, 255) : null,
            'verified' => $verifiedAt,
            'expires'  => $expiresAt,
            'id'       => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM `github_accounts` WHERE `id` = ?')->execute([$id]);
    }

    /**
     * All accounts in the public shape (no ciphertext/nonce), ordered by label.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllPublic(): array
    {
        $rows = $this->pdo->query(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM `github_accounts` a ORDER BY a.`label` ASC'
        )->fetchAll();

        return array_map([$this, 'normalize'], $rows);
    }

    /**
     * @return array<string,mixed>|null public shape
     */
    public function findPublicById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM `github_accounts` a WHERE a.`id` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? $this->normalize($row) : null;
    }

    /**
     * Full row including ciphertext and nonce — for the client factory / vault only.
     *
     * @return array<string,mixed>|null
     */
    public function findWithSecret(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `github_accounts` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Every account with its ciphertext (key rotation only).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllWithSecret(): array
    {
        return $this->pdo->query(
            'SELECT `id`, `token_ciphertext`, `token_nonce` FROM `github_accounts`
              WHERE `token_ciphertext` IS NOT NULL'
        )->fetchAll();
    }

    /**
     * Replace only ciphertext + nonce (key rotation) without touching status.
     */
    public function replaceCiphertext(int $id, string $ciphertext, string $nonce): void
    {
        $this->pdo->prepare(
            'UPDATE `github_accounts` SET `token_ciphertext` = ?, `token_nonce` = ? WHERE `id` = ?'
        )->execute([$ciphertext, $nonce, $id]);
    }

    /**
     * @return array<string,mixed>|null public shape
     */
    public function findByOwner(string $ownerLogin, string $graphqlUrl): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM `github_accounts` a
              WHERE a.`owner_login` = ? AND a.`graphql_url` = ? LIMIT 1'
        );
        $stmt->execute([$ownerLogin, $graphqlUrl]);
        $row = $stmt->fetch();

        return $row !== false ? $this->normalize($row) : null;
    }

    /**
     * Number of projects (tracked or archived) that reference the account.
     */
    public function countProjects(int $id): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `projects` WHERE `account_id` = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param  array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normalize(array $row): array
    {
        $row['has_token']        = (bool) $row['has_token'];
        $row['tracked_projects'] = (int) $row['tracked_projects'];

        return $row;
    }
}
