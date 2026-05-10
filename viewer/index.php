<?php
/**
 * fast-php-log-viewer entry point
 * Served at http://localhost:8080/logs
 */
define('LOG_DIR', getenv('LOG_DIR') ?: '/var/www/html/logs');
define('LOG_DIRS', [
    'App logs'     => LOG_DIR . '/2026',
    'PHP errors'   => dirname(ini_get('error_log') ?: '/var/www/html/logs/php-errors.log'),
    'Debug dumps'  => LOG_DIR,
]);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (isset($_GET['action'])) {
    require_once dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/src/api.php';
    exit;
}

require_once dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/index.php';
