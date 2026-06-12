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
        $this->service = new SshTestService;
    }

    public function test_test_connection_returns_error_when_host_is_empty(): void
    {
        $result = $this->service->testConnection('', 'user', 'pass');

        $this->assertFalse($result['success']);
        $this->assertSame('Host and user are required', $result['error']);
    }

    public function test_test_connection_returns_error_when_user_is_empty(): void
    {
        $result = $this->service->testConnection('host', '', 'pass');

        $this->assertFalse($result['success']);
        $this->assertSame('Host and user are required', $result['error']);
    }

    public function test_test_connection_returns_error_when_no_password_or_key(): void
    {
        $result = $this->service->testConnection('host', 'user', null, null);

        $this->assertFalse($result['success']);
        $this->assertSame('Password or key required', $result['error']);
    }

    public function test_test_connection_with_password_attempts_connection(): void
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

    public function test_test_connection_with_key_attempts_connection(): void
    {
        $result = $this->service->testConnection('invalid.test.host', 'user', null, '/path/to/key');

        // Should fail because host doesn't exist, but command should be built correctly
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('details', $result);
    }

    public function test_custom_port_is_used(): void
    {
        $result = $this->service->testConnection('host', 'user', 'pass', null, 2222);

        // Just verify it runs without error - actual connection will fail
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_build_command_uses_password_auth_for_password(): void
    {
        $cmd = $this->callBuildCommand('host', 'user', 'secret', null, 22);

        $this->assertStringContainsString('PreferredAuthentications=password', $cmd);
        $this->assertStringNotContainsString('PreferredAuthentications=publickey', $cmd);
        $this->assertStringContainsString('sshpass', $cmd);
    }

    public function test_build_command_uses_publickey_auth_for_key(): void
    {
        $cmd = $this->callBuildCommand('host', 'user', null, '/path/to/key', 22);

        $this->assertStringContainsString('PreferredAuthentications=publickey', $cmd);
        $this->assertStringNotContainsString('PreferredAuthentications=password', $cmd);
        $this->assertStringContainsString('-i', $cmd);
    }

    private function callBuildCommand(
        string $host,
        string $user,
        ?string $password,
        ?string $keyPath,
        int $port,
    ): string {
        $method = new \ReflectionMethod(SshTestService::class, 'buildCommand');

        return $method->invoke($this->service, $host, $user, $password, $keyPath, $port);
    }
}
