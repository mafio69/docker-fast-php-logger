<?php

namespace App\Tests\Service;

use App\Service\LogViewerService;
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
        
        $this->assertIsArray($logDirs);
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
        
        $this->assertIsArray($logDirs);
        $this->assertCount(2, $logDirs);
        $this->assertArrayHasKey('Container PHP Errors', $logDirs);
        $this->assertArrayHasKey('Docker Container Logs', $logDirs);
        $this->assertArrayNotHasKey('Host PHP Errors', $logDirs);
    }

    public function testGetLogDirsByTypeHost(): void
    {
        $logDirs = $this->logViewerService->getLogDirsByType('host');
        
        $this->assertIsArray($logDirs);
        $this->assertCount(3, $logDirs);
        $this->assertArrayHasKey('Host PHP Errors', $logDirs);
        $this->assertArrayHasKey('App logs', $logDirs);
        $this->assertArrayHasKey('Debug dumps', $logDirs);
        $this->assertArrayNotHasKey('Container PHP Errors', $logDirs);
    }

    public function testGetLogDirsByTypeInvalid(): void
    {
        $logDirs = $this->logViewerService->getLogDirsByType('invalid');
        
        $this->assertIsArray($logDirs);
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
}