<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    $envFile = dirname(__DIR__).'/.env';
    if (file_exists($envFile) || file_exists("$envFile.dist")) {
        (new Dotenv())->bootEnv($envFile);
    }
}

if ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? '1') {
    umask(0000);
}
