<?php

declare(strict_types=1);

namespace App\GraphQL;

use App\Models\Issue;
use App\Models\Project;

/**
 * ResponseParser — transforms raw GitHub GraphQL v4 JSON payloads into local
 * domain models (Project, Issue).
 *
 * All methods are static and pure: they never produce side-effects and never
 * throw for missing or null fields. Unrecognised field value types emit a PHP
 * warning via trigger_error() rather than throwing an exception, so one bad
 * custom field doesn't abort an entire sync.
 *
 * Expected input shapes match the queries defined in App\GraphQL\Queries:
 *   parseProject()    ← data.user.projectV2 node from FETCH_PROJECT_FIELDS
 *   parseIssueNode()  ← single node from data.user.projectV2.items.nodes
 *                       (FETCH_PROJECT_ITEMS)
 *   parseIssueNodes() ← the full nodes array from the same query
 */
final class ResponseParser
{
    // -------------------------------------------------------------------------
    // Project parsing
    // -------------------------------------------------------------------------

    /**
     * Map a raw `projectV2` node to a Project model.
     *
     * @param  array<string,mixed> $raw   data.user.projectV2 from FETCH_PROJECT_FIELDS
     * @param  string              $owner GitHub login of the project owner
     * @return Project
     */
    public static function parseProject(array $raw, string $owner = ''): Project
    {
        $fields = self::parseFieldDefinitions(
            $raw['fields']['nodes'] ?? []
        );

        return new Project(
            githubId:     $raw['id']               ?? '',
            title:        $raw['title']             ?? '',
            number:       (int) ($raw['number']     ?? 0),
            owner:        $owner,
            description:  $raw['shortDescription']  ?? null,
            createdAt:    $raw['createdAt']          ?? null,
            updatedAt:    $raw['updatedAt']          ?? null,
            creatorLogin: $raw['creator']['login']   ?? null,
            fields:       $fields,
        );
    }

    // -------------------------------------------------------------------------
    // Issue / item parsing
    // -------------------------------------------------------------------------

    /**
     * Map a flat array of project item nodes to an array of Issue models.
     * Nodes whose `content` is not an Issue (DraftIssue, PullRequest, empty)
     * are silently skipped.
     *
     * @param  array<int,array<string,mixed>> $nodes  items.nodes from FETCH_PROJECT_ITEMS
     * @return Issue[]
     */
    public static function parseIssueNodes(array $nodes): array
    {
        $issues = [];

        foreach ($nodes as $node) {
            $issue = self::parseIssueNode($node);

            if ($issue !== null) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }

    /**
     * Map a single project item node to an Issue model.
     * Returns null when the item's content is not a GitHub Issue.
     *
     * @param  array<string,mixed> $node  Single element of items.nodes
     * @return Issue|null
     */
    public static function parseIssueNode(array $node): ?Issue
    {
        $content = $node['content'] ?? [];

        // Skip non-Issue content (DraftIssue, PullRequest, or missing)
        $typename = $content['__typename'] ?? null;

        if ($typename !== null && $typename !== 'Issue') {
            return null;
        }

        // When __typename is absent we check for Issue-specific fields
        if ($typename === null && empty($content['state'])) {
            return null;
        }

        $customFields = self::parseCustomFieldValues(
            $node['fieldValues']['nodes'] ?? []
        );

        return new Issue(
            githubId:    $node['id']                ?? '',
            contentId:   $content['id']             ?? '',
            issueNumber: (int) ($content['number']  ?? 0),
            title:       $content['title']          ?? '',
            body:        $content['body']           ?? null,
            state:       $content['state']          ?? 'OPEN',
            url:         $content['url']            ?? null,
            createdAt:   $content['createdAt']      ?? $node['createdAt'] ?? null,
            updatedAt:   $content['updatedAt']      ?? $node['updatedAt'] ?? null,
            closedAt:    $content['closedAt']       ?? null,
            assignees:   self::parseAssignees($content['assignees']['nodes'] ?? []),
            labels:      self::parseLabels($content['labels']['nodes']       ?? []),
            milestone:   self::parseMilestone($content['milestone']          ?? null),
            customFields: $customFields,
            // Local time fields are never populated from GitHub data
            estimatedHours: null,
            remainingHours: null,
            actualHours:    null,
        );
    }

    // -------------------------------------------------------------------------
    // Field-value extraction
    // -------------------------------------------------------------------------

    /**
     * Extract custom field values from a fieldValues.nodes array.
     * Returns a map of field name (lowercase) → resolved scalar value.
     *
     * Supported concrete types (via inline fragments in queries.php):
     *   ProjectV2ItemFieldTextValue        → string
     *   ProjectV2ItemFieldNumberValue      → float
     *   ProjectV2ItemFieldDateValue        → string (ISO 8601)
     *   ProjectV2ItemFieldSingleSelectValue → string (option name)
     *   ProjectV2ItemFieldIterationValue   → string (iteration title)
     *
     * Unrecognised types emit E_USER_WARNING and are skipped.
     *
     * @param  array<int,array<string,mixed>> $nodes  fieldValues.nodes
     * @return array<string,string|float|null>
     */
    public static function parseCustomFieldValues(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            if (empty($node)) {
                continue;
            }

            $typename  = $node['__typename'] ?? null;
            $fieldName = self::extractFieldName($node);

            if ($fieldName === null) {
                continue;
            }

            $key   = strtolower($fieldName);
            $value = self::resolveFieldValue($node, $typename, $fieldName);

            $result[$key] = $value;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the scalar value of a single fieldValue node.
     * Dispatches by __typename when present, falls back to key-presence heuristic.
     *
     * @param  array<string,mixed> $node
     * @param  string|null         $typename
     * @param  string              $fieldName  For warning messages only
     * @return string|float|null
     */
    private static function resolveFieldValue(
        array   $node,
        ?string $typename,
        string  $fieldName,
    ): string|float|null {
        // Dispatch by explicit __typename (preferred — set when API returns it)
        if ($typename !== null) {
            return match ($typename) {
                'ProjectV2ItemFieldTextValue'         => isset($node['text'])   ? (string) $node['text']   : null,
                'ProjectV2ItemFieldNumberValue'       => isset($node['number']) ? (float)  $node['number'] : null,
                'ProjectV2ItemFieldDateValue'         => isset($node['date'])   ? (string) $node['date']   : null,
                'ProjectV2ItemFieldSingleSelectValue' => isset($node['name'])   ? (string) $node['name']   : null,
                'ProjectV2ItemFieldIterationValue'    => isset($node['title'])  ? (string) $node['title']  : null,
                default => self::warnUnknownType($typename, $fieldName),
            };
        }

        // Fallback heuristic: detect value by key presence (API may omit __typename)
        if (array_key_exists('text', $node)) {
            return isset($node['text']) ? (string) $node['text'] : null;
        }

        if (array_key_exists('number', $node)) {
            return isset($node['number']) ? (float) $node['number'] : null;
        }

        if (array_key_exists('date', $node)) {
            return isset($node['date']) ? (string) $node['date'] : null;
        }

        // 'name' is used by both SingleSelect and IterationValue (title)
        if (array_key_exists('name', $node)) {
            return isset($node['name']) ? (string) $node['name'] : null;
        }

        if (array_key_exists('title', $node)) {
            return isset($node['title']) ? (string) $node['title'] : null;
        }

        return null;
    }

    /**
     * Extract the field name from the nested `field` object within a fieldValue node.
     *
     * @param  array<string,mixed> $node
     * @return string|null
     */
    private static function extractFieldName(array $node): ?string
    {
        $field = $node['field'] ?? null;

        if (!is_array($field)) {
            return null;
        }

        return $field['name'] ?? null;
    }

    /**
     * Parse field definitions from a fields.nodes array (FETCH_PROJECT_FIELDS).
     *
     * @param  array<int,array<string,mixed>> $nodes
     * @return array<int,array<string,mixed>>
     */
    private static function parseFieldDefinitions(array $nodes): array
    {
        $fields = [];

        foreach ($nodes as $node) {
            if (empty($node['id']) || empty($node['name'])) {
                continue;
            }

            $field = [
                'id'       => $node['id'],
                'name'     => $node['name'],
                'dataType' => $node['dataType'] ?? 'TEXT',
            ];

            // Attach options for single-select fields
            if (!empty($node['options'])) {
                $field['options'] = $node['options'];
            }

            // Attach iteration configuration
            if (!empty($node['configuration']['iterations'])) {
                $field['iterations'] = $node['configuration']['iterations'];
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Extract assignee list from assignees.nodes.
     *
     * @param  array<int,array<string,mixed>> $nodes
     * @return array<int,array<string,string|null>>
     */
    private static function parseAssignees(array $nodes): array
    {
        return array_map(
            static fn(array $a): array => [
                'login' => $a['login'] ?? '',
                'name'  => $a['name']  ?? null,
            ],
            $nodes,
        );
    }

    /**
     * Extract label list from labels.nodes.
     *
     * @param  array<int,array<string,mixed>> $nodes
     * @return array<int,array<string,string|null>>
     */
    private static function parseLabels(array $nodes): array
    {
        return array_map(
            static fn(array $l): array => [
                'name'  => $l['name']  ?? '',
                'color' => $l['color'] ?? null,
            ],
            $nodes,
        );
    }

    /**
     * Normalise milestone data or return null when milestone is absent.
     *
     * @param  array<string,mixed>|null $raw
     * @return array<string,mixed>|null
     */
    private static function parseMilestone(?array $raw): ?array
    {
        if ($raw === null || empty($raw['title'])) {
            return null;
        }

        return [
            'title' => $raw['title'],
            'dueOn' => $raw['dueOn']  ?? null,
            'state' => $raw['state']  ?? 'OPEN',
        ];
    }

    /**
     * Emit a PHP warning for an unrecognised field value typename and return null.
     *
     * @param  string $typename
     * @param  string $fieldName
     * @return null
     */
    private static function warnUnknownType(string $typename, string $fieldName): null
    {
        trigger_error(
            sprintf(
                'ResponseParser: unrecognised fieldValue __typename "%s" for field "%s" — skipped',
                $typename,
                $fieldName,
            ),
            E_USER_WARNING,
        );

        return null;
    }
}
