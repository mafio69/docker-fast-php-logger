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

    private string $publicAssetsPath;

    public function __construct(KernelInterface $kernel)
    {
        $projectDir = $kernel->getProjectDir();
        $this->logDir = $projectDir . '/logs';
        $this->autoloadPath = $projectDir . '/vendor/autoload.php';
        $this->frontendBootstrapPath = $projectDir . '/vendor/mafio69/log-viewer/src/Bootstrap/frontend.php';
        $this->publicAssetsPath = $projectDir . '/vendor/mafio69/log-viewer/public';
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

            $content = (string) ob_get_clean();

            return $this->rewriteViewerHtml($content);
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * templates/viewer.php linkuje css/js względnymi ścieżkami (np. "css/style.css"),
     * które zakładają, że plik jest serwowany bezpośrednio z /public paczki.
     * Tu jest osadzony pod /logs, więc trzeba je przepisać na trasę /logs-assets/...
     * obsługiwaną przez getAssetAbsolutePath()/LogController::asset().
     */
    private function rewriteViewerHtml(string $content): string
    {
        $content = str_replace('href="css/', 'href="/logs-assets/css/', $content);
        $content = str_replace('src="js/', 'src="/logs-assets/js/', $content);

        return $content;
    }

    public function getAssetAbsolutePath(string $type, string $assetPath): ?string
    {
        if (!in_array($type, ['css', 'js'], true)) {
            return null;
        }

        if (str_contains($assetPath, '..')) {
            return null;
        }

        $baseDir = realpath($this->publicAssetsPath . '/' . $type);
        if ($baseDir === false) {
            return null;
        }

        $fullPath = realpath($baseDir . '/' . ltrim($assetPath, '/'));
        if ($fullPath === false || !str_starts_with($fullPath, $baseDir . '/')) {
            return null;
        }

        return is_file($fullPath) ? $fullPath : null;
    }

    public function isViewerAvailable(): bool
    {
        return file_exists($this->autoloadPath) && file_exists($this->frontendBootstrapPath);
    }
}
