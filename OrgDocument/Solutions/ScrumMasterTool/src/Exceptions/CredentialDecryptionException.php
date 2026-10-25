<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * CredentialDecryptionException — a stored credential could not be decrypted
 * (wrong key, tampered data, unknown format). The message is deliberately
 * generic: it never contains ciphertext, key material or plaintext.
 */
final class CredentialDecryptionException extends \RuntimeException
{
}
