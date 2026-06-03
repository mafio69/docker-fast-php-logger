<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Command\MonitorCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MonitorCommandTest extends TestCase
{
    private MonitorCommand $command;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->command = new MonitorCommand();
        $this->reflection = new ReflectionClass($this->command);
    }

    private function invokeMethod(string $method, array $args = []): mixed
    {
        $m = $this->reflection->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($this->command, ...$args);
    }

    private function setProperty(string $property, mixed $value): void
    {
        $p = $this->reflection->getProperty($property);
        $p->setAccessible(true);
        $p->setValue($this->command, $value);
    }

    private function getProperty(string $property): mixed
    {
        $p = $this->reflection->getProperty($property);
        $p->setAccessible(true);
        return $p->getValue($this->command);
    }

    // ─── isWorkingHours ──────────────────────────────────────────────────

    public function testIsWorkingHoursWeekdayDuringWork(): void
    {
        // The method uses date('H') and date('N') — test current logic
        $result = $this->invokeMethod('isWorkingHours');
        $hour = (int) date('H');
        $weekday = (int) date('N');
        $expected = $weekday <= 5 && $hour >= 7 && $hour < 17;

        $this->assertSame($expected, $result);
    }

    // ─── log method ──────────────────────────────────────────────────────

    public function testLogWritesToFile(): void
    {
        $tmpDir = sys_get_temp_dir() . '/monitor_test_' . uniqid();
        mkdir($tmpDir, 0777, true);
        $logFile = $tmpDir . '/agent.log';

        $this->setProperty('logFile', $logFile);

        $this->invokeMethod('log', ['Test message']);

        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content);

        unlink($logFile);
        rmdir($tmpDir);
    }

    public function testLogAppendsMultipleMessages(): void
    {
        $tmpDir = sys_get_temp_dir() . '/monitor_test_' . uniqid();
        mkdir($tmpDir, 0777, true);
        $logFile = $tmpDir . '/agent.log';

        $this->setProperty('logFile', $logFile);

        $this->invokeMethod('log', ['First message']);
        $this->invokeMethod('log', ['Second message']);

        $content = file_get_contents($logFile);
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);

        $lines = array_filter(explode("\n", $content));
        $this->assertCount(2, $lines);

        unlink($logFile);
        rmdir($tmpDir);
    }

    // ─── Property defaults ───────────────────────────────────────────────

    public function testDefaultMode(): void
    {
        $this->assertSame('work', $this->getProperty('mode'));
    }

    public function testDefaultWasLocked(): void
    {
        $this->assertFalse($this->getProperty('wasLocked'));
    }

    public function testDefaultAlertTriggered(): void
    {
        $this->assertFalse($this->getProperty('alertTriggered'));
    }

    // ─── Command configuration ───────────────────────────────────────────

    public function testCommandName(): void
    {
        $this->assertSame('monitor', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertSame(
            'Monitoruje Time Doctor i pokazuje alerty',
            $this->command->getDescription()
        );
    }
}
