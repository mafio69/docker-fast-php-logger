<?php

declare(strict_types=1);

namespace App\Service;

use ErrorException;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

class LogViewerService
{
    private string $logDir;

    private string $autoloadPath;

    private string $frontendBootstrapPath;

    public function __construct(KernelInterface $kernel)
    {
        $projectDir = $kernel->getProjectDir();
        $this->logDir = $projectDir . '/logs';
        $this->autoloadPath = $projectDir . '/vendor/autoload.php';
        $this->frontendBootstrapPath = $projectDir . '/vendor/mafio69/log-viewer/src/Bootstrap/frontend.php';
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

    /**
     * Konwertuje warningi/notice na wyjątki, ale respektuje @-suppresję i
     * error_reporting() — bez tego warunku KAŻDY, nawet celowo wyciszony przez
     * @ warning (np. @filemtime/@filesize w mafio69/log-viewer) był
     * konwertowany na wyjątek. To standardowy wzorzec z manuala PHP dla
     * set_error_handler(). Wydzielone jako publiczna metoda statyczna, żeby
     * dało się to przetestować bez odpalania całego renderViewer()/frontend.php.
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public function renderViewer(?string $type = null): string
    {
        set_error_handler([self::class, 'handleError']);

        ob_start();

        try {
            if (!defined('LOG_DIR')) {
                define('LOG_DIR', $this->logDir);
            }
            if (!defined('LOG_DIRS')) {
                define('LOG_DIRS', $type ? $this->getLogDirsByType($type) : $this->getLogDirs());
            }

            require_once $this->autoloadPath;
            require $this->frontendBootstrapPath;

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            restore_error_handler();
        }
    }

    public function isViewerAvailable(): bool
    {
        return file_exists($this->autoloadPath) && file_exists($this->frontendBootstrapPath);
    }
}
