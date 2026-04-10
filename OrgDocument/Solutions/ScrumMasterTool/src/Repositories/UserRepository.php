<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

/**
 * UserRepository — all database access for the `users` table.
 *
 * Security: every query uses PDO prepared statements.
 * password_hash is only returned by the internal findHashByEmail() method
 * which is used exclusively by AuthService for credential verification.
 */
class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Fetch a User by email. Returns null when not found.
     * password_hash is excluded from the returned object.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, password_hash, display_name, github_username, role, created_at
               FROM `users`
              WHERE email = ?
              LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row !== false ? User::fromRow($row) : null;
    }

    /**
     * Fetch a User by primary key. Returns null when not found.
     */
    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, display_name, github_username, role, created_at
               FROM `users`
              WHERE id = ?
              LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row !== false ? User::fromRow($row) : null;
    }

    /**
     * Return the raw password_hash for a given email.
     * Used only by AuthService. Returns null when the email does not exist.
     */
    public function findHashByEmail(string $email): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT password_hash FROM `users` WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row !== false ? $row['password_hash'] : null;
    }

    /**
     * Insert a new user row. Returns the newly created User.
     *
     * @throws \RuntimeException when the password is empty or hashing fails.
     */
    public function create(
        string  $email,
        string  $plainPassword,
        string  $displayName,
        string  $role           = 'member',
        ?string $githubUsername = null,
    ): User {
        if ($plainPassword === '') {
            throw new \RuntimeException('Password must not be empty.');
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `users` (email, password_hash, display_name, github_username, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $displayName, $githubUsername, $role]);

        $id = (int) $this->pdo->lastInsertId();

        // Re-fetch to return the canonical row (picks up DB defaults like created_at)
        $user = $this->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User was inserted but could not be retrieved.');
        }

        return $user;
    }

    /**
     * Return all users ordered by id ASC, without password_hash.
     *
     * Used by AdminController::listUsers() (T017).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT `id`, `email`, `display_name`, `role`,
                    `github_username`, `created_at`, `updated_at`
               FROM `users`
              ORDER BY `id` ASC'
        );

        return $stmt->fetchAll();
    }
}
