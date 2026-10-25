<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * CredentialConfigurationException — the vault cannot be used because APP_KEY
 * is missing or malformed. Only code paths that need the vault fail; the rest
 * of the application keeps working.
 */
final class CredentialConfigurationException extends \RuntimeException
{
}
