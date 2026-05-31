<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\MdViewerController;
use App\Service\MdViewer\MockDataProvider;
use App\Service\MdViewerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Controller\MdViewerController
 */
class MdViewerControllerTest extends TestCase
{
    private MdViewerService $service;
    private MdViewerController $controller;

    protected function setUp(): void
    {
        $this->service = new MdViewerService(new MockDataProvider());
        $this->controller = new MdViewerController($this->service);
    }

    public function testIndexReturnsResponse(): void
    {
        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function testDataReturnsJsonResponse(): void
    {
        $request = new Request([
            'page' => '1',
            'perPage' => '5',
        ]);

        $response = $this->controller->data($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('total', $content);
        $this->assertArrayHasKey('page', $content);
    }

    public function testDataHandlesSearchParameter(): void
    {
        $request = new Request([
            'search' => 'test',
            'page' => '1',
            'perPage' => '10',
        ]);

        $response = $this->controller->data($request);

        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('filtered', $content);
    }

    public function testDataHandlesFilterParameters(): void
    {
        $request = new Request([
            'status' => 'active',
            'category' => 'docs',
            'page' => '1',
            'perPage' => '5',
        ]);

        $response = $this->controller->data($request);

        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $content);
    }
}
