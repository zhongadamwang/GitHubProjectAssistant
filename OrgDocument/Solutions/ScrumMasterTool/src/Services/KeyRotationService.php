<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GitHubAccountRepository;
use PDO;

/**
 * KeyRotationService — re-encrypts every stored token from one APP_KEY to another.
 *
 * All accounts are rewritten in a single transaction: either every token is
 * re-encrypted or none is (a wrong old key aborts before anything changes).
 */
final class KeyRotationService
{
    public function __construct(
        private readonly GitHubAccountRepository $accounts,
        private readonly PDO                     $pdo,
    ) {
    }

    /**
     * @return int number of tokens re-encrypted
     *
     * @throws \App\Exceptions\CredentialDecryptionException when a token cannot be read with $old
     */
    public function rotate(CredentialVault $old, CredentialVault $new): int
    {
        $rows = $this->accounts->findAllWithSecret();

        $this->pdo->beginTransaction();

        try {
            foreach ($rows as $row) {
                $plain     = $old->decrypt((string) $row['token_ciphertext'], (string) $row['token_nonce']);
                $encrypted = $new->encrypt($plain);
                $this->accounts->replaceCiphertext((int) $row['id'], $encrypted['ciphertext'], $encrypted['nonce']);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return count($rows);
    }
}
