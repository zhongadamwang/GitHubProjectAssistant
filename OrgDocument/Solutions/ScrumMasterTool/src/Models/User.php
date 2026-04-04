<?php
declare(strict_types=1);

namespace App\Models;

/**
 * User — immutable value object representing a row from the `users` table.
 *
 * password_hash is intentionally excluded from the public API surface so it
 * can never be accidentally serialised into a JSON response.
 */
final class User
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $email,
        public readonly string  $displayName,
        public readonly string  $role,          // 'admin' | 'member'
        public readonly ?string $githubUsername,
        public readonly string  $createdAt,
    ) {
    }

    /**
     * Build a User from a PDO FETCH_ASSOC row.
     * The caller is responsible for never passing a row that includes
     * password_hash into this constructor.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            email:          $row['email'],
            displayName:    $row['display_name'],
            role:           $row['role'],
            githubUsername: $row['github_username'] ?? null,
            createdAt:      $row['created_at'],
        );
    }

    /**
     * Returns a safe array suitable for JSON API responses.
     * password_hash is never included.
     */
    public function toApiArray(): array
    {
        return [
            'id'              => $this->id,
            'email'           => $this->email,
            'display_name'    => $this->displayName,
            'role'            => $this->role,
            'github_username' => $this->githubUsername,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
