<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logger\SshCommandBuilder;
use PHPUnit\Framework\TestCase;

class SshCommandBuilderTest extends TestCase
{
    private SshCommandBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SshCommandBuilder();
    }

    // ─── Validation ──────────────────────────────────────────────────────

    public function testValidateRequiresHost(): void
    {
        $error = $this->builder->validate('', 'user', 'pass', '');
        $this->assertSame('Host and user are required', $error);
    }

    public function testValidateRequiresUser(): void
    {
        $error = $this->builder->validate('host.com', '', 'pass', '');
        $this->assertSame('Host and user are required', $error);
    }

    public function testValidateRequiresAuthMethod(): void
    {
        $error = $this->builder->validate('host.com', 'user', '', '');
        $this->assertSame('Password or key required', $error);
    }

    public function testValidatePassesWithPassword(): void
    {
        $error = $this->builder->validate('host.com', 'user', 'secret', '');
        $this->assertNull($error);
    }

    public function testValidatePassesWithKey(): void
    {
        $error = $this->builder->validate('host.com', 'user', '', '/path/to/key');
        $this->assertNull($error);
    }

    public function testValidatePassesWithBoth(): void
    {
        $error = $this->builder->validate('host.com', 'user', 'pass', '/path/to/key');
        $this->assertNull($error);
    }

    // ─── Command Building ────────────────────────────────────────────────

    public function testBuildCommandWithPassword(): void
    {
        $cmd = $this->builder->buildTestCommand('192.168.1.1', 'root', 'mypass', '', '22');

        $this->assertStringContainsString('sshpass', $cmd);
        $this->assertStringContainsString('SSHPASS=', $cmd);
        $this->assertStringContainsString("'root@192.168.1.1'", $cmd);
        $this->assertStringContainsString('echo "SSH_OK"', $cmd);
        $this->assertStringContainsString("-p '22'", $cmd);
    }

    public function testBuildCommandWithKey(): void
    {
        $cmd = $this->builder->buildTestCommand('server.com', 'deploy', '', '/home/user/.ssh/id_rsa', '2222');

        $this->assertStringNotContainsString('sshpass', $cmd);
        $this->assertStringContainsString('-i', $cmd);
        $this->assertStringContainsString('/home/user/.ssh/id_rsa', $cmd);
        $this->assertStringContainsString("'deploy@server.com'", $cmd);
        $this->assertStringContainsString("-p '2222'", $cmd);
    }

    public function testBuildCommandPasswordTakesPrecedence(): void
    {
        $cmd = $this->builder->buildTestCommand('host.com', 'user', 'pass', '/key', '22');

        // When password is provided, it uses sshpass even if key is also given
        $this->assertStringContainsString('sshpass', $cmd);
    }

    public function testBuildCommandEscapesSpecialCharacters(): void
    {
        $cmd = $this->builder->buildTestCommand('host.com', "user'name", "pass'word", '', '22');

        // Verify special chars are shell-escaped
        $this->assertStringContainsString("'user'\\''name@host.com'", $cmd);
    }

    // ─── Output Sanitization ─────────────────────────────────────────────

    public function testSanitizeOutputRemovesPassword(): void
    {
        $output = 'Permission denied, password was: secretpass123';
        $sanitized = $this->builder->sanitizeOutput($output, 'secretpass123');

        $this->assertStringNotContainsString('secretpass123', $sanitized);
        $this->assertStringContainsString('***', $sanitized);
    }

    public function testSanitizeOutputWithEmptyPassword(): void
    {
        $output = 'Some error occurred';
        $sanitized = $this->builder->sanitizeOutput($output, '');

        $this->assertSame('Some error occurred', $sanitized);
    }

    public function testSanitizeOutputMultipleOccurrences(): void
    {
        $output = 'pass=secret123 and again secret123 leaked';
        $sanitized = $this->builder->sanitizeOutput($output, 'secret123');

        $this->assertSame('pass=*** and again *** leaked', $sanitized);
    }
}
