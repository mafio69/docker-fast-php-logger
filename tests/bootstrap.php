<?php

declare(strict_types=1);

// Autoload time-agent vendor (Symfony Console etc.)
$timeAgentAutoload = __DIR__ . '/../time-agent/vendor/autoload.php';
if (file_exists($timeAgentAutoload)) {
    require_once $timeAgentAutoload;
}

// Register PSR-4 autoloader for app/src classes
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\Logger\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/../app/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
