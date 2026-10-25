<?php

declare(strict_types=1);

/**
 * cron/generate-app-key.php — print a new random APP_KEY.
 *
 *   php cron/generate-app-key.php
 *
 * Put the printed value in the server .env as APP_KEY=... (keep a backup in a
 * password manager: without it stored GitHub tokens cannot be decrypted and must
 * be entered again).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

fwrite(STDOUT, App\Services\CredentialVault::generateKey() . "\n");
