<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\MdViewer\MockDataProvider;
use App\Service\MdViewerService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\MdViewerService
 */
class MdViewerServiceTest extends TestCase
{
    private MdViewerService $service;

    protected function setUp(): void
    {
        $this->service = new MdViewerService(new MockDataProvider());
    }

    public function testGetDataReturnsPaginatedResults(): void
    {
        $result = $this->service->getData(
            search: null,
            status: null,
            category: null,
            page: 1,
            perPage: 10,
            sortCol: 'id',
            sortDir: 'asc'
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('filtered', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('totalPages', $result);

        $this->assertCount(10, $result['data']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(10, $result['perPage']);
    }

    public function testGetDataFiltersBySearch(): void
    {
        $result = $this->service->getData(
            search: 'document',
            status: null,
            category: null,
            page: 1,
            perPage: 100,
            sortCol: 'id',
            sortDir: 'asc'
        );

        $this->assertGreaterThan(0, $result['filtered']);
        $this->assertLessThanOrEqual($result['total'], $result['filtered']);

        foreach ($result['data'] as $item) {
            $this->assertStringContainsStringIgnoringCase('document', $item->title);
        }
    }

    public function testGetDataFiltersByStatus(): void
    {
        $result = $this->service->getData(
            search: null,
            status: 'active',
            category: null,
            page: 1,
            perPage: 100,
            sortCol: 'id',
            sortDir: 'asc'
        );

        foreach ($result['data'] as $item) {
            $this->assertSame('active', $item->status);
        }
    }

    public function testGetDataFiltersByCategory(): void
    {
        $result = $this->service->getData(
            search: null,
            status: null,
            category: 'docs',
            page: 1,
            perPage: 100,
            sortCol: 'id',
            sortDir: 'asc'
        );

        foreach ($result['data'] as $item) {
            $this->assertSame('docs', $item->category);
        }
    }

    public function testGetDataSortsDescending(): void
    {
        $result = $this->service->getData(
            search: null,
            status: null,
            category: null,
            page: 1,
            perPage: 5,
            sortCol: 'id',
            sortDir: 'desc'
        );

        $ids = array_map(fn($item) => $item->id, $result['data']);
        $this->assertGreaterThan($ids[1], $ids[0]);
    }

    public function testGetDataPagination(): void
    {
        $page1 = $this->service->getData(
            search: null,
            status: null,
            category: null,
            page: 1,
            perPage: 5,
            sortCol: 'id',
            sortDir: 'asc'
        );

        $page2 = $this->service->getData(
            search: null,
            status: null,
            category: null,
            page: 2,
            perPage: 5,
            sortCol: 'id',
            sortDir: 'asc'
        );

        $this->assertNotSame($page1['data'][0]->id, $page2['data'][0]->id);
        $this->assertSame(1, $page1['page']);
        $this->assertSame(2, $page2['page']);
    }

    public function testGetDataItemStructure(): void
    {
        $result = $this->service->getData(
            search: null,
            status: null,
            category: null,
            page: 1,
            perPage: 1,
            sortCol: 'id',
            sortDir: 'asc'
        );

        $item = $result['data'][0];

        $this->assertObjectHasProperty('id', $item);
        $this->assertObjectHasProperty('title', $item);
        $this->assertObjectHasProperty('status', $item);
        $this->assertObjectHasProperty('category', $item);
        $this->assertObjectHasProperty('modified', $item);
        $this->assertObjectHasProperty('size', $item);
    }
}
