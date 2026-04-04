<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Project — value object representing a GitHub Projects v2 project as stored
 * locally. Populated by ResponseParser from FETCH_PROJECT_FIELDS / FETCH_PROJECT_ITEMS
 * responses and persisted to the `projects` table by ProjectRepository.
 *
 * `fields` is a list of field-definition records:
 *   ['id' => string, 'name' => string, 'dataType' => string, 'options'? => array]
 */
final class Project
{
    /**
     * @param string                    $githubId      Node ID from GitHub (globally unique)
     * @param string                    $title
     * @param int                       $number        Project number (shown in URL)
     * @param string                    $owner         GitHub login of the owner
     * @param string|null               $description   shortDescription from API
     * @param string|null               $createdAt     ISO 8601
     * @param string|null               $updatedAt     ISO 8601
     * @param string|null               $creatorLogin
     * @param array<int,array<string,mixed>> $fields   Field definitions
     */
    public function __construct(
        public readonly string  $githubId,
        public readonly string  $title,
        public readonly int     $number,
        public readonly string  $owner,
        public readonly ?string $description,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $creatorLogin,
        public readonly array   $fields = [],
    ) {
    }

    /**
     * Build a Project from a plain associative array (e.g. from a DB row or
     * a previously serialised toArray() snapshot).
     *
     * @param  array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            githubId:     $data['github_id']      ?? $data['githubId']      ?? '',
            title:        $data['title']           ?? '',
            number:       (int) ($data['number']   ?? 0),
            owner:        $data['owner']           ?? '',
            description:  $data['description']     ?? null,
            createdAt:    $data['created_at']      ?? $data['createdAt']     ?? null,
            updatedAt:    $data['updated_at']      ?? $data['updatedAt']     ?? null,
            creatorLogin: $data['creator_login']   ?? $data['creatorLogin']  ?? null,
            fields:       $data['fields']          ?? [],
        );
    }

    /**
     * Serialise to a plain array for DB inserts, snapshot JSON, or API responses.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'github_id'     => $this->githubId,
            'title'         => $this->title,
            'number'        => $this->number,
            'owner'         => $this->owner,
            'description'   => $this->description,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
            'creator_login' => $this->creatorLogin,
            'fields'        => $this->fields,
        ];
    }
}
