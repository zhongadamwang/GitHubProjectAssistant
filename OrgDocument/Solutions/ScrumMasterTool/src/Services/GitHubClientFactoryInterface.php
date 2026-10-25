<?php

declare(strict_types=1);

namespace App\Services;

/**
 * GitHubClientFactoryInterface — builds GitHub API clients for stored accounts.
 * Exists so tests can substitute a fake GitHub without network access.
 */
interface GitHubClientFactoryInterface
{
    /**
     * Client for a stored account (decrypts its token).
     *
     * @param array<string,mixed> $account Row from GitHubAccountRepository::findWithSecret()
     *
     * @throws \App\Exceptions\CredentialConfigurationException when APP_KEY is missing/invalid
     * @throws \App\Exceptions\CredentialDecryptionException    when the stored token cannot be decrypted
     * @throws \RuntimeException                                when the account has no token
     */
    public function forAccount(array $account): GitHubAccountClientInterface;

    /**
     * Client for a token that is not stored (yet) — used while creating/replacing a token.
     */
    public function forToken(string $token, string $graphqlUrl, string $ownerType = 'organization'): GitHubAccountClientInterface;
}
