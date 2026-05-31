<?php

declare(strict_types=1);

namespace App\Controller;

use Mariusz\Logger\DualLogger;
use PDO;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class DashboardController
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
    }

    public function index(): Response
    {
        $logger = DualLogger::create(
            logDir: '/var/www/html/logs',
            minLevel: LogLevel::DEBUG,
            timezone: $_ENV['APP_TIMEZONE'] ?? 'Europe/Warsaw',
        );

        $logger->debug('Dashboard loaded', ['php' => PHP_VERSION]);

        // DB check
        $dbStatus = 'not connected';
        $dbOk = false;
        try {
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $_ENV['DB_HOST'] ?? 'db',
                    $_ENV['DB_PORT'] ?? '3306',
                    $_ENV['DB_DATABASE'] ?? 'app',
                ),
                $_ENV['DB_USERNAME'] ?? 'app',
                $_ENV['DB_PASSWORD'] ?? 'secret',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
            $dbStatus = 'connected';
            $dbOk = true;
        } catch (\PDOException $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        $appEnv = $_ENV['APP_ENV'] ?? 'unknown';
        $html = $this->twig->render('dashboard/index.html.twig', [
            'php_version' => PHP_VERSION,
            'env' => htmlspecialchars($appEnv),
            'db_status' => $dbStatus,
            'db_ok' => $dbOk,
            'date' => date('Y-m-d H:i'),
            'year' => date('Y'),
        ]);

        return new Response($html);
    }
}
