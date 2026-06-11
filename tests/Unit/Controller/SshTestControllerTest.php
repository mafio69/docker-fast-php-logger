<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SshTestController;
use App\Service\SshTestService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \App\Controller\SshTestController
 */
class SshTestControllerTest extends TestCase
{
    private SshTestService $service;
    private SshTestController $controller;

    protected function setUp(): void
    {
        $this->service = new SshTestService();
        $this->controller = new SshTestController($this->service);
    }

    public function testTestWithValidDataReturnsJsonResponse(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'host' => 'test.example.com',
                'user' => 'testuser',
                'pass' => 'testpass',
                'port' => 22,
            ])
        );

        $response = $this->controller->test($request);

        $this->assertSame(400, $response->getStatusCode()); // Will fail because host doesn't exist
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testTestWithMissingHostReturnsError(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'host' => '',
                'user' => 'testuser',
                'pass' => 'testpass',
            ])
        );

        $response = $this->controller->test($request);
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertSame('Host and user are required', $content['error']);
    }

    public function testTestWithMissingCredentialsReturnsError(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'host' => 'test.example.com',
                'user' => 'testuser',
            ])
        );

        $response = $this->controller->test($request);
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertSame('Password or key required', $content['error']);
    }

    public function testTestWithKeyAuthentication(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'host' => 'test.example.com',
                'user' => 'testuser',
                'key' => '/path/to/key',
            ])
        );

        $response = $this->controller->test($request);

        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $content);
    }
}
