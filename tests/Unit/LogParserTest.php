<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logger\LogParser;
use PHPUnit\Framework\TestCase;

class LogParserTest extends TestCase
{
    private LogParser $parser;

    protected function setUp(): void
    {
        $this->parser = new LogParser();
    }

    public function testParseLineWithValidEntry(): void
    {
        $line = '[2026-05-01 08:12:03] [INFO] [app/index.php:12] User logged in {"user_id":42}';
        $result = $this->parser->parseLine($line);

        $this->assertSame('2026-05-01 08:12:03', $result['date']);
        $this->assertSame('INFO', $result['level']);
        $this->assertSame('app/index.php:12', $result['location']);
        $this->assertSame('User logged in', $result['message']);
        $this->assertSame('{"user_id":42}', $result['json']);
    }

    public function testParseLineWithoutJson(): void
    {
        $line = '[2026-05-01 09:00:00] [DEBUG] [boot.php:1] App booted';
        $result = $this->parser->parseLine($line);

        $this->assertSame('2026-05-01 09:00:00', $result['date']);
        $this->assertSame('DEBUG', $result['level']);
        $this->assertSame('boot.php:1', $result['location']);
        $this->assertSame('App booted', $result['message']);
        $this->assertNull($result['json']);
    }

    public function testParseLineWithArrayJson(): void
    {
        $line = '[2026-05-01 10:00:00] [WARNING] [test.php:5] Multiple errors [{"id":1},{"id":2}]';
        $result = $this->parser->parseLine($line);

        $this->assertSame('WARNING', $result['level']);
        $this->assertSame('Multiple errors', $result['message']);
        $this->assertSame('[{"id":1},{"id":2}]', $result['json']);
    }

    public function testParseLineWithEmptyLocation(): void
    {
        $line = '[2026-05-01 11:00:00] [ERROR] [] Something failed';
        $result = $this->parser->parseLine($line);

        $this->assertSame('ERROR', $result['level']);
        $this->assertSame('', $result['location']);
        $this->assertSame('Something failed', $result['message']);
    }

    public function testParseLineWithInvalidFormat(): void
    {
        $line = 'This is not a valid log line';
        $result = $this->parser->parseLine($line);

        $this->assertArrayHasKey('raw', $result);
        $this->assertSame('This is not a valid log line', $result['raw']);
        $this->assertArrayNotHasKey('date', $result);
    }

    public function testParseLineWithEmptyString(): void
    {
        $result = $this->parser->parseLine('');

        $this->assertArrayHasKey('raw', $result);
        $this->assertSame('', $result['raw']);
    }

    public function testParseLineCriticalLevel(): void
    {
        $line = '[2026-05-01 22:58:12] [CRITICAL] [db.php:99] Database connection lost {"host":"db","port":3306}';
        $result = $this->parser->parseLine($line);

        $this->assertSame('CRITICAL', $result['level']);
        $this->assertSame('Database connection lost', $result['message']);
        $this->assertSame('{"host":"db","port":3306}', $result['json']);
    }

    public function testLoadLogFileWithValidFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_');
        file_put_contents($tmpFile, implode("\n", [
            '[2026-05-01 08:00:00] [INFO] [test.php:1] Line one',
            '[2026-05-01 09:00:00] [DEBUG] [test.php:2] Line two',
        ]));

        $lines = $this->parser->loadLogFile($tmpFile);

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Line one', $lines[0]);
        $this->assertStringContainsString('Line two', $lines[1]);

        unlink($tmpFile);
    }

    public function testLoadLogFileStripsPhpHeader(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_');
        file_put_contents($tmpFile, implode("\n", [
            '<?php // Sellasist header',
            '[2026-05-01 08:00:00] [INFO] [test.php:1] Actual log',
        ]));

        $lines = $this->parser->loadLogFile($tmpFile);

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Actual log', $lines[0]);

        unlink($tmpFile);
    }

    public function testLoadLogFileNonExistent(): void
    {
        $lines = $this->parser->loadLogFile('/nonexistent/file.log');

        $this->assertSame([], $lines);
    }

    public function testLoadLogFileSkipsEmptyLines(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_');
        file_put_contents($tmpFile, "[2026-05-01 08:00:00] [INFO] [t:1] First\n\n\n[2026-05-01 09:00:00] [INFO] [t:2] Second\n");

        $lines = $this->parser->loadLogFile($tmpFile);

        $this->assertCount(2, $lines);

        unlink($tmpFile);
    }

    public function testParseFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'log_test_');
        file_put_contents($tmpFile, implode("\n", [
            '[2026-05-01 08:00:00] [INFO] [app.php:1] First entry {"key":"val"}',
            '[2026-05-01 09:00:00] [ERROR] [app.php:2] Second entry',
            'Unparseable line',
        ]));

        $entries = $this->parser->parseFile($tmpFile);

        $this->assertCount(3, $entries);
        $this->assertSame('INFO', $entries[0]['level']);
        $this->assertSame('ERROR', $entries[1]['level']);
        $this->assertArrayHasKey('raw', $entries[2]);

        unlink($tmpFile);
    }

    public function testFilterByLevel(): void
    {
        $entries = [
            ['level' => 'INFO', 'message' => 'msg1'],
            ['level' => 'ERROR', 'message' => 'msg2'],
            ['level' => 'INFO', 'message' => 'msg3'],
            ['level' => 'DEBUG', 'message' => 'msg4'],
        ];

        $result = $this->parser->filterByLevel($entries, 'info');
        $this->assertCount(2, $result);
        $this->assertSame('msg1', $result[0]['message']);
        $this->assertSame('msg3', $result[1]['message']);
    }

    public function testFilterByLevelEmptyString(): void
    {
        $entries = [
            ['level' => 'INFO', 'message' => 'msg1'],
            ['level' => 'ERROR', 'message' => 'msg2'],
        ];

        $result = $this->parser->filterByLevel($entries, '');
        $this->assertCount(2, $result);
    }

    public function testFilterByLevelNoMatches(): void
    {
        $entries = [
            ['level' => 'INFO', 'message' => 'msg1'],
        ];

        $result = $this->parser->filterByLevel($entries, 'CRITICAL');
        $this->assertSame([], $result);
    }
}
