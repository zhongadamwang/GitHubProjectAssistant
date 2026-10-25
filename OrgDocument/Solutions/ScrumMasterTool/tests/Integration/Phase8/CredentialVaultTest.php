<?php

declare(strict_types=1);

namespace Tests\Integration\Phase8;

use App\Exceptions\CredentialConfigurationException;
use App\Exceptions\CredentialDecryptionException;
use App\Repositories\GitHubAccountRepository;
use App\Services\CredentialVault;
use App\Services\KeyRotationService;

/**
 * T047 — CredentialVault (authenticated encryption) and key rotation.
 */
final class CredentialVaultTest extends Phase8TestCase
{
    private const TOKEN = 'ghp_ExampleTokenValue1234567890';

    /** @return array<string,array{bool}> */
    public static function schemeProvider(): array
    {
        return ['sodium' => [false], 'openssl fallback' => [true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('schemeProvider')]
    public function test_round_trip(bool $forceOpenSsl): void
    {
        $vault = new CredentialVault(CredentialVault::generateKey(), $forceOpenSsl);
        $enc   = $vault->encrypt(self::TOKEN);

        $this->assertSame(self::TOKEN, $vault->decrypt($enc['ciphertext'], $enc['nonce']));
        $this->assertStringNotContainsString(self::TOKEN, $enc['ciphertext']);
        $this->assertStringStartsWith($forceOpenSsl ? 'o:' : 's:', $enc['ciphertext']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('schemeProvider')]
    public function test_every_encryption_uses_a_fresh_nonce(bool $forceOpenSsl): void
    {
        $vault = new CredentialVault(CredentialVault::generateKey(), $forceOpenSsl);

        $a = $vault->encrypt(self::TOKEN);
        $b = $vault->encrypt(self::TOKEN);

        $this->assertNotSame($a['nonce'], $b['nonce']);
        $this->assertNotSame($a['ciphertext'], $b['ciphertext']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('schemeProvider')]
    public function test_wrong_key_is_rejected_without_leaking(bool $forceOpenSsl): void
    {
        $enc = (new CredentialVault(CredentialVault::generateKey(), $forceOpenSsl))->encrypt(self::TOKEN);
        $other = new CredentialVault(CredentialVault::generateKey(), $forceOpenSsl);

        try {
            $other->decrypt($enc['ciphertext'], $enc['nonce']);
            $this->fail('Expected CredentialDecryptionException');
        } catch (CredentialDecryptionException $e) {
            $this->assertStringNotContainsString(self::TOKEN, $e->getMessage());
            $this->assertStringNotContainsString($enc['ciphertext'], $e->getMessage());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('schemeProvider')]
    public function test_tampered_ciphertext_is_rejected(bool $forceOpenSsl): void
    {
        $vault = new CredentialVault(CredentialVault::generateKey(), $forceOpenSsl);
        $enc   = $vault->encrypt(self::TOKEN);

        [$scheme, $b64] = explode(':', $enc['ciphertext'], 2);
        $raw = base64_decode($b64, true);
        $raw[0] = $raw[0] ^ "\x01";

        $this->expectException(CredentialDecryptionException::class);
        $vault->decrypt($scheme . ':' . base64_encode($raw), $enc['nonce']);
    }

    /** @return array<string,array{string,string}> */
    public static function malformedProvider(): array
    {
        return [
            'not base64'      => ['s:***', 'AAAA'],
            'empty payload'   => ['s:', 'AAAA'],
            'unknown scheme'  => ['x:AAAA', 'AAAA'],
            'no scheme'       => ['AAAA', 'AAAA'],
            'bad nonce'       => ['s:AAAAAAAAAAAAAAAAAAAAAAAA', '%%%'],
            'short nonce'     => ['s:AAAAAAAAAAAAAAAAAAAAAAAA', 'AAAA'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedProvider')]
    public function test_malformed_input_raises_decryption_exception(string $cipher, string $nonce): void
    {
        $vault = new CredentialVault(CredentialVault::generateKey());

        $this->expectException(CredentialDecryptionException::class);
        $vault->decrypt($cipher, $nonce);
    }

    /** @return array<string,array{string}> */
    public static function badKeyProvider(): array
    {
        return ['empty' => [''], 'spaces' => ['   '], 'not base64' => ['***'], 'too short' => [base64_encode('short')], 'too long' => [base64_encode(str_repeat('a', 33))]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badKeyProvider')]
    public function test_missing_or_invalid_key_is_a_configuration_error(string $key): void
    {
        $vault = new CredentialVault($key);

        $this->assertFalse($vault->isConfigured());
        $this->expectException(CredentialConfigurationException::class);
        $vault->encrypt(self::TOKEN);
    }

    public function test_hint_returns_last_four_characters_only(): void
    {
        $this->assertSame(substr(self::TOKEN, -4), CredentialVault::hint(self::TOKEN));
        $this->assertSame('', CredentialVault::hint('short'));
    }

    public function test_debug_output_does_not_expose_the_key(): void
    {
        $key   = CredentialVault::generateKey();
        $vault = new CredentialVault($key);

        ob_start();
        var_dump($vault);
        $dump = (string) ob_get_clean();

        $this->assertStringNotContainsString($key, $dump);
        $this->assertStringNotContainsString($key, (string) $vault);
    }

    public function test_generated_keys_are_valid_and_unique(): void
    {
        $a = CredentialVault::generateKey();
        $b = CredentialVault::generateKey();

        $this->assertNotSame($a, $b);
        $this->assertSame(32, strlen((string) base64_decode($a, true)));
        $this->assertTrue((new CredentialVault($a))->isConfigured());
    }

    public function test_key_rotation_reencrypts_all_tokens_atomically(): void
    {
        $accounts = new GitHubAccountRepository(static::$pdo);
        $oldVault = new CredentialVault(CredentialVault::generateKey());
        $newVault = new CredentialVault(CredentialVault::generateKey());

        $ids = [];
        foreach (['one' => 'ghp_TokenOne111111', 'two' => 'ghp_TokenTwo222222'] as $owner => $token) {
            $id  = $accounts->create(['label' => $owner, 'owner_login' => $owner]);
            $enc = $oldVault->encrypt($token);
            $accounts->saveToken($id, $enc['ciphertext'], $enc['nonce'], CredentialVault::hint($token));
            $ids[$token] = $id;
        }

        $count = (new KeyRotationService($accounts, static::$pdo))->rotate($oldVault, $newVault);

        $this->assertSame(2, $count);
        foreach ($ids as $token => $id) {
            $row = $accounts->findWithSecret($id);
            $this->assertSame($token, $newVault->decrypt($row['token_ciphertext'], $row['token_nonce']));
        }
    }

    public function test_key_rotation_with_wrong_old_key_changes_nothing(): void
    {
        $accounts = new GitHubAccountRepository(static::$pdo);
        $realKey  = new CredentialVault(CredentialVault::generateKey());

        $id  = $accounts->create(['label' => 'one', 'owner_login' => 'one']);
        $enc = $realKey->encrypt('ghp_TokenOne111111');
        $accounts->saveToken($id, $enc['ciphertext'], $enc['nonce'], '1111');
        $before = $accounts->findWithSecret($id);

        try {
            (new KeyRotationService($accounts, static::$pdo))
                ->rotate(new CredentialVault(CredentialVault::generateKey()), new CredentialVault(CredentialVault::generateKey()));
            $this->fail('Expected CredentialDecryptionException');
        } catch (CredentialDecryptionException) {
            $after = $accounts->findWithSecret($id);
            $this->assertSame($before['token_ciphertext'], $after['token_ciphertext']);
            $this->assertSame($before['token_nonce'], $after['token_nonce']);
        }
    }
}
