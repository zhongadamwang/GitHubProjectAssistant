<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8\Support;

use App\Services\CredentialVault;
use App\Services\GitHubAccountClientInterface;
use App\Services\GitHubClientFactoryInterface;

/**
 * FakeGitHubClientFactory — hands out a FakeGitHubClient, but still decrypts the stored
 * token with the real CredentialVault so decryption failures behave like production.
 */
final class FakeGitHubClientFactory implements GitHubClientFactoryInterface
{
    /**
     * Plain tokens the factory was asked to use (so tests can check which token reached GitHub).
     *
     * @var string[]
     */
    public array $tokensSeen = [];

    /** @var array<string,FakeGitHubClient> client overrides keyed by owner login */
    public array $clientsByOwner = [];

    public function __construct(
        public FakeGitHubClient $client,
        private readonly CredentialVault $vault,
    ) {
    }

    public function forAccount(array $account): GitHubAccountClientInterface
    {
        if (empty($account['token_ciphertext']) || empty($account['token_nonce'])) {
            throw new \RuntimeException('GitHub account has no access token.');
        }

        $this->tokensSeen[] = $this->vault->decrypt((string) $account['token_ciphertext'], (string) $account['token_nonce']);

        return $this->clientsByOwner[(string) ($account['owner_login'] ?? '')] ?? $this->client;
    }

    public function forToken(string $token, string $graphqlUrl, string $ownerType = 'organization'): GitHubAccountClientInterface
    {
        $this->tokensSeen[] = $token;

        return $this->client;
    }
}
