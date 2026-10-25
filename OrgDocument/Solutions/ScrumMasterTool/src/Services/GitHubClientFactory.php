<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\GitHubApiException;

/**
 * GitHubClientFactory — production implementation: decrypts the stored token with the
 * CredentialVault and returns a cURL-based GitHubGraphQLService.
 */
final class GitHubClientFactory implements GitHubClientFactoryInterface
{
    private readonly GraphqlEndpointValidator $endpointValidator;

    public function __construct(private readonly CredentialVault $vault, ?GraphqlEndpointValidator $endpointValidator = null)
    {
        $this->endpointValidator = $endpointValidator ?? new GraphqlEndpointValidator();
    }

    /** {@inheritdoc} */
    public function forAccount(array $account): GitHubAccountClientInterface
    {
        if (empty($account['token_ciphertext']) || empty($account['token_nonce'])) {
            throw new \RuntimeException('GitHub account has no access token.');
        }

        $token = $this->vault->decrypt((string) $account['token_ciphertext'], (string) $account['token_nonce']);

        return $this->forToken(
            $token,
            (string) ($account['graphql_url'] ?? 'https://api.github.com/graphql'),
            (string) ($account['owner_type'] ?? 'organization'),
        );
    }

    /** {@inheritdoc} */
    public function forToken(string $token, string $graphqlUrl, string $ownerType = 'organization'): GitHubAccountClientInterface
    {
        // Re-checked on every use (not only when the account is saved) so a custom endpoint whose DNS
        // changes later cannot point the server at an internal address (SSRF / DNS rebinding).
        if ($graphqlUrl !== GraphqlEndpointValidator::DEFAULT_ENDPOINT && ($problem = $this->endpointValidator->validate($graphqlUrl)) !== null) {
            throw new GitHubApiException(0, [], 'The configured GitHub endpoint is not allowed: ' . $problem);
        }

        return new GitHubGraphQLService($token, $graphqlUrl, $ownerType === 'user' ? 'user' : 'organization');
    }
}
