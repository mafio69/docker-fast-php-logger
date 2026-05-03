<?php
/**
 * fast-php-log-viewer entry point
 * Served at http://localhost:8080/logs
 */
define('LOG_DIR', getenv('LOG_DIR') ?: '/var/www/html/logs');

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (isset($_GET['action'])) {
    require_once dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/src/api.php';
    exit;
}

require_once dirname(__DIR__) . '/vendor/mafio69/fast-php-log-viewer/index.php';
