<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Issue — value object representing a GitHub Issue as stored locally.
 * Populated from FETCH_PROJECT_ITEMS by ResponseParser and persisted to
 * the `issues` table by IssueRepository.
 *
 * Time-tracking fields (`estimatedHours`, `remainingHours`, `actualHours`)
 * are read from GitHub Project number fields (see ResponseParser); GitHub is
 * the source of truth and the dashboard only displays them. Null means the
 * field is empty or missing in GitHub.
 *
 * `assignees`    — array of ['login' => string, 'name' => string|null]
 * `labels`       — array of ['name'  => string, 'color' => string|null]
 * `milestone`    — ['title' => string, 'dueOn' => string|null, 'state' => string]|null
 * `customFields` — key-value map of field name => resolved scalar value
 */
final class Issue
{
    /**
     * @param string                         $githubId       Node ID of the ProjectV2Item
     * @param string                         $contentId      Node ID of the underlying Issue
     * @param int                            $issueNumber    Issue number within the repository
     * @param string                         $title
     * @param string|null                    $body
     * @param string                         $state          'OPEN' | 'CLOSED'
     * @param string|null                    $url
     * @param string|null                    $createdAt      ISO 8601
     * @param string|null                    $updatedAt      ISO 8601 (GitHub)
     * @param string|null                    $closedAt       ISO 8601
     * @param array<int,array<string,mixed>> $assignees
     * @param array<int,array<string,mixed>> $labels
     * @param array<string,mixed>|null       $milestone
     * @param array<string,mixed>            $customFields   field name → scalar
     * @param float|null                     $estimatedHours From the GitHub number field; null when empty
     * @param float|null                     $remainingHours From the GitHub number field; null when empty
     * @param float|null                     $actualHours    From the GitHub number field; null when empty
     */
    public function __construct(
        public readonly string  $githubId,
        public readonly string  $contentId,
        public readonly int     $issueNumber,
        public readonly string  $title,
        public readonly ?string $body,
        public readonly string  $state,
        public readonly ?string $url,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly ?string $closedAt,
        public readonly array   $assignees    = [],
        public readonly array   $labels       = [],
        public readonly ?array  $milestone    = null,
        public readonly array   $customFields = [],
        public readonly ?float  $estimatedHours = null,
        public readonly ?float  $remainingHours = null,
        public readonly ?float  $actualHours    = null,
    ) {
    }

    /**
     * Build an Issue from a plain associative array (DB row or prior toArray() output).
     *
     * Supports both snake_case (DB) and camelCase (snapshot) key conventions.
     *
     * @param  array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            githubId:       $data['github_id']       ?? $data['githubId']       ?? '',
            contentId:      $data['content_id']      ?? $data['contentId']      ?? '',
            issueNumber:    (int) ($data['issue_number'] ?? $data['issueNumber'] ?? 0),
            title:          $data['title']            ?? '',
            body:           $data['body']             ?? null,
            state:          $data['state']            ?? 'OPEN',
            url:            $data['url']              ?? null,
            createdAt:      $data['created_at']       ?? $data['createdAt']      ?? null,
            updatedAt:      $data['updated_at']       ?? $data['updatedAt']      ?? null,
            closedAt:       $data['closed_at']        ?? $data['closedAt']       ?? null,
            assignees:      $data['assignees']        ?? [],
            labels:         $data['labels']           ?? [],
            milestone:      $data['milestone']        ?? null,
            customFields:   $data['custom_fields']    ?? $data['customFields']   ?? [],
            estimatedHours: isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : null,
            remainingHours: isset($data['remaining_hours']) ? (float) $data['remaining_hours'] : null,
            actualHours:    isset($data['actual_hours'])    ? (float) $data['actual_hours']    : null,
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
            'github_id'       => $this->githubId,
            'content_id'      => $this->contentId,
            'issue_number'    => $this->issueNumber,
            'title'           => $this->title,
            'body'            => $this->body,
            'state'           => $this->state,
            'url'             => $this->url,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
            'closed_at'       => $this->closedAt,
            'assignees'       => $this->assignees,
            'labels'          => $this->labels,
            'milestone'       => $this->milestone,
            'custom_fields'   => $this->customFields,
            'estimated_hours' => $this->estimatedHours,
            'remaining_hours' => $this->remainingHours,
            'actual_hours'    => $this->actualHours,
        ];
    }

    /** Returns true if the issue has been closed on GitHub. */
    public function isClosed(): bool
    {
        return strtoupper($this->state) === 'CLOSED';
    }
}
