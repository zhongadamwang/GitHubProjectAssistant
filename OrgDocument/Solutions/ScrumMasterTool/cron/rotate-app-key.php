<?php

declare(strict_types=1);

/**
 * cron/rotate-app-key.php — re-encrypt all stored GitHub tokens with a new APP_KEY.
 *
 *   php cron/rotate-app-key.php --old=<current APP_KEY> --new=<new APP_KEY>
 *
 * Steps: generate a new key (php cron/generate-app-key.php), run this script,
 * then replace APP_KEY in .env with the new value. The rotation is atomic.
 *
 * Exit codes: 0 ok, 1 invalid arguments or undecryptable token (nothing changed), 2 unexpected error.
 */

use App\Repositories\GitHubAccountRepository;
use App\Services\CredentialVault;
use App\Services\KeyRotationService;

$rootDir = dirname(__DIR__);

require $rootDir . '/vendor/autoload.php';

if (file_exists($rootDir . '/.env')) {
    Dotenv\Dotenv::createImmutable($rootDir)->load();
}

$old = null;
$new = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--old=')) {
        $old = substr($arg, 6);
    } elseif (str_starts_with($arg, '--new=')) {
        $new = substr($arg, 6);
    }
}

if ($old === null || $new === null || $old === '' || $new === '') {
    fwrite(STDERR, "Usage: php cron/rotate-app-key.php --old=<current key> --new=<new key>\n");
    exit(1);
}

try {
    $oldVault = new CredentialVault($old);
    $newVault = new CredentialVault($new);

    if (!$oldVault->isConfigured() || !$newVault->isConfigured()) {
        fwrite(STDERR, "Both keys must be 32 random bytes, base64-encoded.\n");
        exit(1);
    }

    /** @var \Psr\Container\ContainerInterface $container */
    $container = require $rootDir . '/config/container.php';

    $service = new KeyRotationService(
        $container->get(GitHubAccountRepository::class),
        $container->get(PDO::class),
    );

    $count = $service->rotate($oldVault, $newVault);
    fwrite(STDOUT, "Re-encrypted {$count} token(s). Now set APP_KEY in .env to the new key.\n");
    exit(0);
} catch (App\Exceptions\CredentialDecryptionException) {
    fwrite(STDERR, "A stored token could not be decrypted with the old key. Nothing was changed.\n");
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Key rotation failed: ' . get_class($e) . "\n");
    exit(2);
}
