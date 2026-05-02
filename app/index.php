<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Mariusz\Logger\DualLogger;
use Psr\Log\LogLevel;

// Logger is pre-configured — logs go to /var/www/html/logs
$logger = DualLogger::create(
    logDir:   __DIR__ . '/../logs',
    minLevel: LogLevel::DEBUG,
    timezone: $_ENV['APP_TIMEZONE'] ?? 'Europe/Warsaw',
);

// ── Demo: all log levels ──────────────────────────────────────────────────────

$logger->debug('App booted', ['php' => PHP_VERSION, 'env' => $_ENV['APP_ENV'] ?? 'unknown']);

$logger->info('Request received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri'    => $_SERVER['REQUEST_URI'],
]);

$logger->warning('Login failed', [
    'email'    => 'jan@example.com',   // → masked automatically
    'token'    => 'abc123xyz',         // → masked automatically
    'attempts' => 3,
]);

$logger->error('Order failed', [
    'order' => ['id' => 42, 'items' => 3],
    'user'  => new class { public int $id = 7; public string $name = 'Jan'; },
]);

try {
    throw new \RuntimeException('Connection refused', 500);
} catch (\Throwable $e) {
    $logger->critical('Unexpected error', ['exception' => $e]);
}

// ── Demo: DB connection ───────────────────────────────────────────────────────

$dbStatus = 'not connected';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST']     ?? 'db',
            $_ENV['DB_PORT']     ?? '3306',
            $_ENV['DB_DATABASE'] ?? 'app',
        ),
        $_ENV['DB_USERNAME'] ?? 'app',
        $_ENV['DB_PASSWORD'] ?? 'secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $dbStatus = 'connected ✓';
    $logger->info('Database connected');
} catch (\PDOException $e) {
    $dbStatus = 'error: ' . $e->getMessage();
    $logger->error('Database connection failed', ['exception' => $e]);
}

// ── Output ────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>docker-fast-logger</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 2rem; }
        h1   { color: #4ec9b0; }
        .ok  { color: #4ec9b0; }
        .err { color: #f44747; }
        p    { margin: .4rem 0; }
    </style>
</head>
<body>
    <h1>docker-fast-logger</h1>
    <p>PHP: <span class="ok"><?= PHP_VERSION ?></span></p>
    <p>ENV: <span class="ok"><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'unknown') ?></span></p>
    <p>DB:  <span class="<?= str_starts_with($dbStatus, 'connected') ? 'ok' : 'err' ?>"><?= htmlspecialchars($dbStatus) ?></span></p>
    <p>Logs written to <code>./logs/</code> — check the files or open the log viewer.</p>
</body>
</html>
