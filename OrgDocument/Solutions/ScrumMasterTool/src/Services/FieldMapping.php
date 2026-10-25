<?php

declare(strict_types=1);

namespace App\Services;

/**
 * FieldMapping — which GitHub Project field holds which value.
 *
 * Roles: estimated / remaining / actual hours (NUMBER fields) and iteration (ITERATION
 * field). Names come from the global settings (REPO_FIELD_*) and can be overridden per
 * project; matching against GitHub is case-insensitive.
 */
final class FieldMapping
{
    public const ROLES = ['estimated', 'remaining', 'actual', 'iteration'];

    public const EXPECTED_TYPES = [
        'estimated' => 'NUMBER',
        'remaining' => 'NUMBER',
        'actual'    => 'NUMBER',
        'iteration' => 'ITERATION',
    ];

    /**
     * Effective names: project override (non-blank) on top of the global names.
     *
     * @param  array<string,string>      $global
     * @param  array<string,string>|null $override
     * @return array<string,string> role → field name
     */
    public static function resolve(array $global, ?array $override): array
    {
        $result = [];

        foreach (self::ROLES as $role) {
            $name = trim((string) ($override[$role] ?? ''));
            $result[$role] = $name !== '' ? $name : trim((string) ($global[$role] ?? ''));
        }

        return $result;
    }

    /**
     * Clean admin input: only known roles, trimmed, non-blank, at most 100 characters.
     *
     * @param  array<string,mixed> $input
     * @return array{0: array<string,string>, 1: array<string,string>} [clean mapping, errors by role]
     */
    public static function sanitize(array $input): array
    {
        $clean  = [];
        $errors = [];

        foreach ($input as $role => $name) {
            if (!in_array($role, self::ROLES, true)) {
                $errors[(string) $role] = 'Unknown field role.';
                continue;
            }
            if (!is_string($name)) {
                $errors[$role] = 'Field name must be text.';
                continue;
            }
            $name = trim($name);
            if ($name === '') {
                continue; // blank = use the default
            }
            if (mb_strlen($name) > 100) {
                $errors[$role] = 'Field name is too long (max 100 characters).';
                continue;
            }
            $clean[$role] = $name;
        }

        return [$clean, $errors];
    }

    /**
     * Check a project's field definitions against a mapping.
     *
     * @param  array<int,array<string,mixed>> $projectFields  definitions: id, name, dataType
     * @param  array<string,string>           $mapping        effective role → name
     * @return array<int,array{role:string,name:string,expected_type:string,status:string,data_type:?string}>
     *         status: found | missing | wrong_type
     */
    public static function validate(array $projectFields, array $mapping): array
    {
        $byName = [];
        foreach ($projectFields as $field) {
            $byName[strtolower((string) ($field['name'] ?? ''))] = $field;
        }

        $results = [];
        foreach (self::ROLES as $role) {
            $name     = $mapping[$role] ?? '';
            $expected = self::EXPECTED_TYPES[$role];
            $field    = $byName[strtolower($name)] ?? null;

            if ($field === null) {
                $status = 'missing';
                $type   = null;
            } else {
                $type   = strtoupper((string) ($field['dataType'] ?? ''));
                $status = $type === $expected ? 'found' : 'wrong_type';
            }

            $results[] = ['role' => $role, 'name' => $name, 'expected_type' => $expected, 'status' => $status, 'data_type' => $type];
        }

        return $results;
    }
}
