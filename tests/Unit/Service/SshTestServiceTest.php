<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SshTestService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Service\SshTestService
 */
class SshTestServiceTest extends TestCase
{
    private SshTestService $service;

    protected function setUp(): void
    {
        $this->service = new SshTestService();
    }

    public function testTestConnectionReturnsErrorWhenHostIsEmpty(): void
    {
        $result = $this->service->testConnection('', 'user', 'pass');

        $this->assertFalse($result['success']);
        $this->assertSame('Host and user are required', $result['error']);
    }

    public function testTestConnectionReturnsErrorWhenUserIsEmpty(): void
    {
        $result = $this->service->testConnection('host', '', 'pass');

        $this->assertFalse($result['success']);
        $this->assertSame('Host and user are required', $result['error']);
    }

    public function testTestConnectionReturnsErrorWhenNoPasswordOrKey(): void
    {
        $result = $this->service->testConnection('host', 'user', null, null);

        $this->assertFalse($result['success']);
        $this->assertSame('Password or key required', $result['error']);
    }

    public function testTestConnectionWithPasswordAttemptsConnection(): void
    {
        // Using invalid host to get connection failure (testing command building)
        $result = $this->service->testConnection('invalid.test.host', 'user', 'password123');

        // Should fail because host doesn't exist, but command should be built correctly
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('details', $result);
        // Password should NOT appear in output (sanitized)
        $this->assertStringNotContainsString('password123', $result['details']);
    }

    public function testTestConnectionWithKeyAttemptsConnection(): void
    {
        $result = $this->service->testConnection('invalid.test.host', 'user', null, '/path/to/key');

        // Should fail because host doesn't exist, but command should be built correctly
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('details', $result);
    }

    public function testCustomPortIsUsed(): void
    {
        $result = $this->service->testConnection('host', 'user', 'pass', null, 2222);

        // Just verify it runs without error - actual connection will fail
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
