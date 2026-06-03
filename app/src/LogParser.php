<?php

declare(strict_types=1);

namespace App\Logger;

/**
 * Parses log lines in the format:
 * [datetime] [LEVEL] [location] message {json}
 */
class LogParser
{
    /**
     * Parse a single log line into structured data.
     *
     * @return array{date?: string, level?: string, location?: string, message?: string, json?: string|null, raw?: string}
     */
    public function parseLine(string $line): array
    {
        if (!preg_match('/^\[([^\]]+)\] \[([A-Z]+)\] \[([^\]]*)\] (.*)$/', $line, $m)) {
            return ['raw' => $line];
        }

        $rest = $m[4];
        $json = null;

        if (preg_match('/(\{.*\}|\[.*\])$/', $rest, $jm)) {
            $json = $jm[1];
            $rest = trim(substr($rest, 0, -strlen($json)));
        }

        return [
            'date' => $m[1],
            'level' => $m[2],
            'location' => $m[3],
            'message' => $rest,
            'json' => $json,
        ];
    }

    /**
     * Load a log file into an array of lines (strips PHP headers).
     *
     * @return string[]
     */
    public function loadLogFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        // Strip Sellasist PHP header
        if ($lines && str_starts_with($lines[0], '<?php')) {
            array_shift($lines);
        }

        return $lines;
    }

    /**
     * Parse all lines from a log file.
     *
     * @return array<int, array{date?: string, level?: string, location?: string, message?: string, json?: string|null, raw?: string}>
     */
    public function parseFile(string $path): array
    {
        $lines = $this->loadLogFile($path);

        return array_map(fn(string $line) => $this->parseLine($line), $lines);
    }

    /**
     * Filter parsed entries by log level.
     *
     * @param array<int, array<string, mixed>> $entries
     * @return array<int, array<string, mixed>>
     */
    public function filterByLevel(array $entries, string $level): array
    {
        if ($level === '') {
            return $entries;
        }

        return array_values(
            array_filter($entries, fn(array $e) => ($e['level'] ?? '') === strtoupper($level))
        );
    }
}
