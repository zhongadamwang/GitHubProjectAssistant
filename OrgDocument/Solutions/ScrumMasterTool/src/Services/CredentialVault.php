<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CredentialConfigurationException;
use App\Exceptions\CredentialDecryptionException;

/**
 * CredentialVault — authenticated encryption for secrets stored in the database
 * (GitHub access tokens).
 *
 * - Key: APP_KEY, 32 random bytes, base64-encoded (generate with
 *   `php cron/generate-app-key.php`).
 * - Preferred scheme: libsodium `secretbox` (XSalsa20-Poly1305), scheme tag "s".
 *   Fallback when libsodium is missing: AES-256-GCM via OpenSSL, scheme tag "o".
 * - A fresh random nonce is used for every value. The ciphertext field is
 *   "<scheme>:<base64>", the nonce field "<base64>".
 * - Any failure (wrong key, tampering, unknown scheme) raises
 *   CredentialDecryptionException without leaking material.
 *
 * Secrets are never written to logs; this class neither logs nor exposes the
 * key or plaintext through __toString/var_dump.
 */
final class CredentialVault
{
    private const KEY_BYTES = 32;

    private const GCM_TAG_BYTES = 16;

    private ?string $key = null;

    /**
     * @param string $base64Key     APP_KEY value (may be empty — vault use then throws)
     * @param bool   $forceOpenSsl  Use the OpenSSL scheme even if libsodium exists (tests)
     */
    public function __construct(
        private readonly string $base64Key,
        private readonly bool   $forceOpenSsl = false,
    ) {
    }

    /**
     * Generate a new random APP_KEY value.
     */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(self::KEY_BYTES));
    }

    /**
     * Last characters of a token for display ("••••abcd"); empty for very short values.
     */
    public static function hint(string $token): string
    {
        return strlen($token) >= 8 ? substr($token, -4) : '';
    }

    /**
     * True when a usable key is configured.
     */
    public function isConfigured(): bool
    {
        try {
            $this->key();

            return true;
        } catch (CredentialConfigurationException) {
            return false;
        }
    }

    /**
     * @return array{ciphertext: string, nonce: string}
     *
     * @throws CredentialConfigurationException when APP_KEY is missing/invalid
     */
    public function encrypt(string $plaintext): array
    {
        $key = $this->key();

        if ($this->useSodium()) {
            $nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);

            return ['ciphertext' => 's:' . base64_encode($cipher), 'nonce' => base64_encode($nonce)];
        }

        $nonce  = random_bytes(12);
        $tag    = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', self::GCM_TAG_BYTES);

        if ($cipher === false) {
            throw new CredentialConfigurationException('Encryption is not available on this server.');
        }

        return ['ciphertext' => 'o:' . base64_encode($cipher . $tag), 'nonce' => base64_encode($nonce)];
    }

    /**
     * @throws CredentialConfigurationException when APP_KEY is missing/invalid
     * @throws CredentialDecryptionException    when the value cannot be decrypted
     */
    public function decrypt(string $ciphertext, string $nonce): string
    {
        $key = $this->key();

        [$scheme, $payload] = array_pad(explode(':', $ciphertext, 2), 2, '');
        $rawCipher = base64_decode($payload, true);
        $rawNonce  = base64_decode($nonce, true);

        if ($rawCipher === false || $rawNonce === false || $rawCipher === '') {
            throw new CredentialDecryptionException('Stored credential is unreadable.');
        }

        $plain = match ($scheme) {
            's'     => $this->decryptSodium($rawCipher, $rawNonce, $key),
            'o'     => $this->decryptOpenSsl($rawCipher, $rawNonce, $key),
            default => false,
        };

        if ($plain === false) {
            throw new CredentialDecryptionException('Stored credential could not be decrypted (wrong key or corrupted data).');
        }

        return $plain;
    }

    public function __debugInfo(): array
    {
        return ['configured' => $this->isConfigured()];
    }

    public function __toString(): string
    {
        return 'CredentialVault';
    }

    private function useSodium(): bool
    {
        return !$this->forceOpenSsl && function_exists('sodium_crypto_secretbox');
    }

    private function decryptSodium(string $cipher, string $nonce, string $key): string|false
    {
        if (!function_exists('sodium_crypto_secretbox_open') || strlen($nonce) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return false;
        }

        return sodium_crypto_secretbox_open($cipher, $nonce, $key);
    }

    private function decryptOpenSsl(string $cipher, string $nonce, string $key): string|false
    {
        if (strlen($cipher) <= self::GCM_TAG_BYTES || strlen($nonce) !== 12) {
            return false;
        }

        $tag  = substr($cipher, -self::GCM_TAG_BYTES);
        $body = substr($cipher, 0, -self::GCM_TAG_BYTES);

        return openssl_decrypt($body, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    }

    /**
     * @throws CredentialConfigurationException
     */
    private function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        if (trim($this->base64Key) === '') {
            throw new CredentialConfigurationException(
                'APP_KEY is not set. Generate one with "php cron/generate-app-key.php" and add it to .env.'
            );
        }

        $raw = base64_decode(trim($this->base64Key), true);

        if ($raw === false || strlen($raw) !== self::KEY_BYTES) {
            throw new CredentialConfigurationException(
                'APP_KEY must be 32 random bytes, base64-encoded (see "php cron/generate-app-key.php").'
            );
        }

        return $this->key = $raw;
    }
}
