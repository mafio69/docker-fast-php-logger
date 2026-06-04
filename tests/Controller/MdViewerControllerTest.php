<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MdViewerControllerTest extends WebTestCase
{
    public function testMdViewerEndpointReturns200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mdviewer');
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('MD Viewer', $client->getResponse()->getContent());
    }

    public function testMdViewerPageContainsDataTableStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mdviewer');
        
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('data-table', $content);
        $this->assertStringContainsString('DATATABLE', $content);
    }
}