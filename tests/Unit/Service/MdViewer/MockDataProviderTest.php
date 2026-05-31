<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\MdViewer;

use App\Service\MdViewer\MockDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MockDataProvider - verifies filtering, sorting, pagination logic.
 */
final class MockDataProviderTest extends TestCase
{
    private MockDataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new MockDataProvider();
    }

    public function testReturnsDefaultPagination(): void
    {
        $result = $this->provider->getData();

        $this->assertCount(25, $result['data']);
        $this->assertEquals(150, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(6, $result['totalPages']);
    }

    public function testFiltersBySearch(): void
    {
        $result = $this->provider->getData(search: 'document_001');

        $this->assertLessThan(150, $result['filtered']);
        $this->assertTrue(str_contains($result['data'][0]->title, '001'));
    }

    public function testFiltersByStatus(): void
    {
        $result = $this->provider->getData(status: 'active');

        $this->assertLessThanOrEqual(150, $result['filtered']);
        foreach ($result['data'] as $item) {
            $this->assertEquals('active', $item->status);
        }
    }

    public function testSortsDescending(): void
    {
        $result = $this->provider->getData(sortCol: 'id', sortDir: 'desc', perPage: 5);

        $this->assertEquals('150', $result['data'][0]->id);
        $this->assertEquals('149', $result['data'][1]->id);
    }

    public function testPaginationPage2(): void
    {
        $page1 = $this->provider->getData(page: 1, perPage: 10);
        $page2 = $this->provider->getData(page: 2, perPage: 10);

        $this->assertEquals('001', $page1['data'][0]->id);
        $this->assertEquals('011', $page2['data'][0]->id);
    }

    public function testCombinesFilters(): void
    {
        $result = $this->provider->getData(
            search: 'document',
            status: 'active',
            category: 'docs',
            perPage: 100
        );

        $this->assertLessThanOrEqual(150, $result['filtered']);
        foreach ($result['data'] as $item) {
            $this->assertEquals('active', $item->status);
            $this->assertEquals('docs', $item->category);
        }
    }
}
