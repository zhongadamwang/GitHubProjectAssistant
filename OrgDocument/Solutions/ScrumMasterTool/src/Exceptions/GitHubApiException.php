<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * GitHubApiException — thrown when the GitHub GraphQL API returns an
 * unexpected HTTP status code or a GraphQL-level errors array.
 *
 * The PAT is never stored in this exception; message text is kept generic
 * so it is safe to log without redacting.
 */
final class GitHubApiException extends RuntimeException
{
    /**
     * @param int             $httpStatus   HTTP response status code (0 = cURL-level failure)
     * @param string[]        $graphqlErrors  Extracted error messages from the GraphQL "errors" key
     * @param string          $message      Human-readable summary
     * @param Throwable|null  $previous
     */
    public function __construct(
        private readonly int   $httpStatus,
        private readonly array $graphqlErrors = [],
        string                 $message = '',
        ?Throwable             $previous = null,
    ) {
        parent::__construct($message ?: $this->buildMessage(), 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return string[] */
    public function getGraphqlErrors(): array
    {
        return $this->graphqlErrors;
    }

    private function buildMessage(): string
    {
        if ($this->graphqlErrors !== []) {
            return sprintf(
                'GitHub GraphQL error(s): %s',
                implode('; ', $this->graphqlErrors)
            );
        }

        return sprintf('GitHub API request failed with HTTP %d', $this->httpStatus);
    }

    /**
     * Factory: build from a raw GraphQL "errors" array as returned by the API.
     *
     * @param  int                           $httpStatus
     * @param  array<array<string, mixed>>   $rawErrors   e.g. [['message' => '...', 'locations' => ...]]
     */
    public static function fromGraphqlErrors(int $httpStatus, array $rawErrors): self
    {
        $messages = array_map(
            static fn(array $e): string => $e['message'] ?? 'Unknown GraphQL error',
            $rawErrors
        );

        return new self($httpStatus, $messages);
    }
}
