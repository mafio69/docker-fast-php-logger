<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SSHControllerTest extends WebTestCase
{
    public function testSshConnectionsEndpointReturns405ForGetMethod(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/ssh-connections');
        
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testSshConnectionsEndpointReturns400ForMissingHost(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/ssh-connections', [], [], [], json_encode([
            'user' => 'testuser',
            'pass' => 'testpass'
        ]));
        
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Host and user are required', $response['error']);
    }
}