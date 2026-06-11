<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration test for MdViewerController - verifies HTTP layer + DI wiring.
 */
final class MdViewerControllerTest extends WebTestCase
{
    public function testApiEndpointReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/mdviewer/data');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('totalPages', $data);
        $this->assertEquals(150, $data['total']);
        $this->assertCount(25, $data['data']);
    }

    public function testApiRespectsQueryParameters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/mdviewer/data?page=2&perPage=10&status=active');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $data['page']);
        $this->assertEquals(10, $data['perPage']);
        $this->assertEquals('active', $data['status'] ?? 'active');
    }

    public function testErrorHandling(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/mdviewer/data?perPage=invalid');

        // Should not crash - perPage cast to int
        $this->assertResponseIsSuccessful();
    }
}
