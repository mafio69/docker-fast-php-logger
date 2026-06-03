<?php
/**
 * fast-php-log-viewer entry point
 * Served at http://localhost:8080/logs
 */

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    define('LOG_DIR', getenv('LOG_DIR') ?: '/var/www/html/logs');
    define('LOG_DIRS', [
        'App logs'     => LOG_DIR . '/2026',
        'PHP errors'   => dirname(ini_get('error_log') ?: '/var/www/html/logs/php-errors.log'),
        'Debug dumps'  => LOG_DIR,
    ]);

    $autoloader = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoloader)) {
        throw new RuntimeException('Vendor autoloader not found. Run: composer install');
    }
    require_once $autoloader;

    if (isset($_GET['action'])) {
        $apiPath = dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/src/api.php';
        if (!file_exists($apiPath)) {
            throw new RuntimeException('Log viewer API not found. Run: composer install');
        }
        require_once $apiPath;
        exit;
    }

    $viewerPath = dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/index.php';
    if (!file_exists($viewerPath)) {
        throw new RuntimeException('Log viewer not found. Run: composer install');
    }
    require_once $viewerPath;
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Error</title>';
    echo '<style>body{background:#0d0d0d;color:#ac4142;font:14px/1.6 monospace;padding:40px}pre{background:#111;border:1px solid #2a2a2a;padding:16px;border-radius:4px;color:#c5c8c6;overflow-x:auto;margin-top:12px}</style>';
    echo '</head><body>';
    echo '<h2>' . htmlspecialchars($e->getMessage()) . '</h2>';
    echo '<p style="color:#888">' . htmlspecialchars(basename($e->getFile())) . ':' . $e->getLine() . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</body></html>';
}
