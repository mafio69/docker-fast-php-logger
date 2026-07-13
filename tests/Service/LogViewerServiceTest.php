<?php

namespace App\Tests\Service;

use App\Service\LogViewerService;
use ErrorException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class LogViewerServiceTest extends TestCase
{
    private KernelInterface $kernel;
    private LogViewerService $logViewerService;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn('/tmp/test-project');
        
        $this->logViewerService = new LogViewerService($this->kernel);
    }

    public function testGetLogDirs(): void
    {
        $logDirs = $this->logViewerService->getLogDirs();
        
        $this->assertArrayHasKey('Container PHP Errors', $logDirs);
        $this->assertArrayHasKey('Docker Container Logs', $logDirs);
        $this->assertArrayHasKey('Host PHP Errors', $logDirs);
        $this->assertArrayHasKey('App logs', $logDirs);
        $this->assertArrayHasKey('Debug dumps', $logDirs);
        
        $this->assertEquals('/var/log/php-fpm/error.log', $logDirs['Container PHP Errors']);
        $this->assertEquals('/var/log/supervisor/php-fpm.log', $logDirs['Docker Container Logs']);
        $this->assertEquals('/tmp/test-project/logs/php-errors.log', $logDirs['Host PHP Errors']);
        $this->assertEquals('/tmp/test-project/logs/2026', $logDirs['App logs']);
        $this->assertEquals('/tmp/test-project/logs', $logDirs['Debug dumps']);
    }

    public function testGetLogDirsByTypeContainer(): void
    {
        $logDirs = $this->logViewerService->getLogDirsByType('container');
        
        $this->assertCount(2, $logDirs);
        $this->assertArrayHasKey('Container PHP Errors', $logDirs);
        $this->assertArrayHasKey('Docker Container Logs', $logDirs);
        $this->assertArrayNotHasKey('Host PHP Errors', $logDirs);
    }

    public function testGetLogDirsByTypeHost(): void
    {
        $logDirs = $this->logViewerService->getLogDirsByType('host');
        
        $this->assertCount(3, $logDirs);
        $this->assertArrayHasKey('Host PHP Errors', $logDirs);
        $this->assertArrayHasKey('App logs', $logDirs);
        $this->assertArrayHasKey('Debug dumps', $logDirs);
        $this->assertArrayNotHasKey('Container PHP Errors', $logDirs);
    }

    public function testGetLogDirsByTypeInvalid(): void
    {
        $logDirs = $this->logViewerService->getLogDirsByType('invalid');
        
        $this->assertCount(5, $logDirs);
        $this->assertArrayHasKey('Container PHP Errors', $logDirs);
        $this->assertArrayHasKey('Host PHP Errors', $logDirs);
    }

    public function testIsViewerAvailableReturnsFalseWhenFileNotExists(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn('/tmp/non-existent');
        
        $service = new LogViewerService($kernel);

        $this->assertFalse($service->isViewerAvailable());
    }

    /**
     * Regresja na bug "headers already sent": stary handler konwertował
     * KAŻDY warning na ErrorException, ignorując @-suppresję. To sprawiało,
     * że np. @filemtime()/@filesize() w mafio69/log-viewer rzucały wyjątek
     * zamiast być po cichu zignorowane.
     */
    public function testHandleErrorRespectsAtSuppression(): void
    {
        $originalReporting = error_reporting();
        error_reporting($originalReporting & ~E_WARNING);

        try {
            $result = LogViewerService::handleError(E_WARNING, 'suppressed warning', __FILE__, __LINE__);

            $this->assertFalse($result, 'Stłumiony (@) warning nie powinien rzucać wyjątku, tylko zwrócić false.');
        } finally {
            error_reporting($originalReporting);
        }
    }

    public function testHandleErrorThrowsForUnsuppressedErrors(): void
    {
        $originalReporting = error_reporting(E_ALL);

        try {
            $this->expectException(ErrorException::class);
            $this->expectExceptionMessage('boom');

            LogViewerService::handleError(E_WARNING, 'boom', __FILE__, __LINE__);
        } finally {
            error_reporting($originalReporting);
        }
    }
}