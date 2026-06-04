<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class LogViewerService
{
    private string $logDir;
    private string $viewerPath;
    private string $apiPath;

    public function __construct(KernelInterface $kernel)
    {
        $projectDir = $kernel->getProjectDir();
        $this->logDir = $projectDir . '/logs';
        $this->viewerPath = $projectDir . '/vendor/mafio69/log-viewer/index.php';
        $this->apiPath = $projectDir . '/vendor/mafio69/log-viewer/src/api.php';
    }

    public function getLogDirs(): array
    {
        return [
            'Container PHP Errors' => '/var/log/php-fpm/error.log',
            'Docker Container Logs' => '/var/log/supervisor/php-fpm.log',
            'Host PHP Errors' => $this->logDir . '/php-errors.log',
            'App logs' => $this->logDir . '/2026',
            'Debug dumps' => $this->logDir,
        ];
    }

    public function getLogDirsByType(string $type): array
    {
        if ($type === 'container') {
            return [
                'Container PHP Errors' => '/var/log/php-fpm/error.log',
                'Docker Container Logs' => '/var/log/supervisor/php-fpm.log',
            ];
        } elseif ($type === 'host') {
            return [
                'Host PHP Errors' => $this->logDir . '/php-errors.log',
                'App logs' => $this->logDir . '/2026',
                'Debug dumps' => $this->logDir,
            ];
        }
        
        return $this->getLogDirs();
    }

    public function renderViewer(string $type = null): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            define('LOG_DIR', $this->logDir);
            define('LOG_DIRS', $type ? $this->getLogDirsByType($type) : $this->getLogDirs());

            if (isset($_GET['action'])) {
                if (file_exists($this->apiPath)) {
                    require_once $this->apiPath;
                    exit;
                }
            }

            if (file_exists($this->viewerPath)) {
                require_once $this->viewerPath;
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo $e->getMessage();
        }
    }

    public function isViewerAvailable(): bool
    {
        return file_exists($this->viewerPath);
    }
}