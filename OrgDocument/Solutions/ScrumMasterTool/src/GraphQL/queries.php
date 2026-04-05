<?php

declare(strict_types=1);

namespace App\GraphQL;

/**
 * Named GitHub GraphQL v4 query templates.
 *
 * All queries use GraphQL variables exclusively — no string interpolation.
 * Inject variables as an associative array alongside the query string when
 * calling GitHubGraphQLService::query().
 *
 * Usage:
 *   Queries::get('FETCH_VIEWER')          // returns query string
 *   Queries::variables('FETCH_VIEWER')    // returns variable schema description
 */
final class Queries
{
    /**
     * Minimal viewer check — verifies the PAT is valid and returns the
     * authenticated user's login name.
     *
     * Variables : (none)
     * Returns   : data.viewer.login (string)
     */
    public const FETCH_VIEWER = <<<'GRAPHQL'
        query FetchViewer {
          viewer {
            login
          }
        }
        GRAPHQL;

    /**
     * Fetches project metadata and all custom field definitions for a
     * GitHub Projects v2 project identified by owner login and project number.
     *
     * Variables:
     *   $owner  : String!  — GitHub login (user or organisation)
     *   $number : Int!     — Project number (visible in the project URL)
     *
     * Returns:
     *   data.user.projectV2 (or data.organization.projectV2) containing:
     *     id, title, number, shortDescription, createdAt, updatedAt,
     *     creator.login,
     *     fields.nodes[] { id, name, dataType }
     */
    public const FETCH_PROJECT_FIELDS = <<<'GRAPHQL'
        query FetchProjectFields($owner: String!, $number: Int!) {
          user(login: $owner) {
            projectV2(number: $number) {
              id
              title
              number
              shortDescription
              createdAt
              updatedAt
              creator {
                login
              }
              fields(first: 50) {
                nodes {
                  ... on ProjectV2Field {
                    id
                    name
                    dataType
                  }
                  ... on ProjectV2SingleSelectField {
                    id
                    name
                    dataType
                    options {
                      id
                      name
                    }
                  }
                  ... on ProjectV2IterationField {
                    id
                    name
                    dataType
                    configuration {
                      iterations {
                        id
                        title
                        startDate
                        duration
                      }
                    }
                  }
                }
              }
            }
          }
        }
        GRAPHQL;

    /**
     * Fetches all project items (issues) for a Projects v2 project with
     * cursor-based pagination. Retrieves the underlying issue content plus
     * all custom field values using inline fragments for each concrete type.
     *
     * Variables:
     *   $owner  : String!  — GitHub login (user or organisation)
     *   $number : Int!     — Project number
     *   $after  : String   — Pagination cursor (null for first page)
     *
     * Returns:
     *   data.user.projectV2.items containing:
     *     pageInfo { hasNextPage endCursor }
     *     nodes[] {
     *       id,
     *       createdAt, updatedAt,
     *       content { ... on Issue { ... } }
     *       fieldValues.nodes[] { ... on ProjectV2ItemField*Value }
     *     }
     *
     * Max 100 items per page (GitHub hard limit for ProjectV2ItemConnection).
     */
    public const FETCH_PROJECT_ITEMS = <<<'GRAPHQL'
        query FetchProjectItems($owner: String!, $number: Int!, $after: String) {
          user(login: $owner) {
            projectV2(number: $number) {
              id
              title
              items(first: 100, after: $after) {
                pageInfo {
                  hasNextPage
                  endCursor
                }
                nodes {
                  id
                  createdAt
                  updatedAt
                  content {
                    ... on Issue {
                      id
                      number
                      title
                      body
                      state
                      url
                      createdAt
                      updatedAt
                      closedAt
                      assignees(first: 20) {
                        nodes {
                          login
                          name
                        }
                      }
                      labels(first: 20) {
                        nodes {
                          name
                          color
                        }
                      }
                      milestone {
                        title
                        dueOn
                        state
                      }
                    }
                  }
                  fieldValues(first: 50) {
                    nodes {
                      ... on ProjectV2ItemFieldTextValue {
                        text
                        field {
                          ... on ProjectV2Field {
                            id
                            name
                          }
                        }
                      }
                      ... on ProjectV2ItemFieldNumberValue {
                        number
                        field {
                          ... on ProjectV2Field {
                            id
                            name
                          }
                        }
                      }
                      ... on ProjectV2ItemFieldDateValue {
                        date
                        field {
                          ... on ProjectV2Field {
                            id
                            name
                          }
                        }
                      }
                      ... on ProjectV2ItemFieldSingleSelectValue {
                        name
                        optionId
                        field {
                          ... on ProjectV2SingleSelectField {
                            id
                            name
                          }
                        }
                      }
                      ... on ProjectV2ItemFieldIterationValue {
                        title
                        startDate
                        duration
                        field {
                          ... on ProjectV2IterationField {
                            id
                            name
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
        GRAPHQL;

    /**
     * Returns the GraphQL query string for the given query name.
     *
     * @param  string $name  One of: FETCH_VIEWER, FETCH_PROJECT_FIELDS, FETCH_PROJECT_ITEMS
     * @return string
     * @throws \InvalidArgumentException if the query name is not defined
     */
    public static function get(string $name): string
    {
        return match ($name) {
            'FETCH_VIEWER'          => self::FETCH_VIEWER,
            'FETCH_PROJECT_FIELDS'  => self::FETCH_PROJECT_FIELDS,
            'FETCH_PROJECT_ITEMS'   => self::FETCH_PROJECT_ITEMS,
            default                 => throw new \InvalidArgumentException(
                "Unknown GraphQL query: '{$name}'. "
                . "Valid names: FETCH_VIEWER, FETCH_PROJECT_FIELDS, FETCH_PROJECT_ITEMS"
            ),
        };
    }

    /**
     * Returns the required variables schema for a named query (for documentation
     * and validation purposes).
     *
     * @param  string $name
     * @return array<string, string>  Map of variable name → type description
     */
    public static function variables(string $name): array
    {
        return match ($name) {
            'FETCH_VIEWER'         => [],
            'FETCH_PROJECT_FIELDS' => ['owner' => 'String!', 'number' => 'Int!'],
            'FETCH_PROJECT_ITEMS'  => ['owner' => 'String!', 'number' => 'Int!', 'after' => 'String'],
            default                => throw new \InvalidArgumentException(
                "Unknown GraphQL query: '{$name}'"
            ),
        };
    }
}
