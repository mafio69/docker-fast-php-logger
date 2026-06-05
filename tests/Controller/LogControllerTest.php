<?php

namespace App\Tests\Controller;

use App\Controller\LogController;
use App\Service\LogViewerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogControllerTest extends TestCase
{
    public function testLogsEndpointReturns500WhenViewerNotAvailable(): void
    {
        // Mock the LogViewerService to return false for isViewerAvailable
        $logViewerService = $this->createMock(LogViewerService::class);
        $logViewerService->method('isViewerAvailable')->willReturn(false);
        
        $controller = new LogController($logViewerService);
        $request = new Request();
        
        $response = $controller->index($request);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Log viewer not found', $response->getContent());
    }

    public function testLogsEndpointReturns200WhenViewerAvailable(): void
    {
        // Mock the LogViewerService to return true for isViewerAvailable
        $logViewerService = $this->createMock(LogViewerService::class);
        $logViewerService->method('isViewerAvailable')->willReturn(true);
        
        $controller = new LogController($logViewerService);
        $request = new Request();
        
        $response = $controller->index($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLogsWithContainerType(): void
    {
        // Mock the LogViewerService
        $logViewerService = $this->createMock(LogViewerService::class);
        $logViewerService->method('isViewerAvailable')->willReturn(true);
        $logViewerService->expects($this->once())
            ->method('renderViewer')
            ->with('container');
        
        $controller = new LogController($logViewerService);
        $request = new Request(['type' => 'container']);
        
        $response = $controller->index($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLogsWithHostType(): void
    {
        // Mock the LogViewerService
        $logViewerService = $this->createMock(LogViewerService::class);
        $logViewerService->method('isViewerAvailable')->willReturn(true);
        $logViewerService->expects($this->once())
            ->method('renderViewer')
            ->with('host');
        
        $controller = new LogController($logViewerService);
        $request = new Request(['type' => 'host']);
        
        $response = $controller->index($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testLogsWithoutType(): void
    {
        // Mock the LogViewerService
        $logViewerService = $this->createMock(LogViewerService::class);
        $logViewerService->method('isViewerAvailable')->willReturn(true);
        $logViewerService->expects($this->once())
            ->method('renderViewer')
            ->with(null);
        
        $controller = new LogController($logViewerService);
        $request = new Request();
        
        $response = $controller->index($request);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}