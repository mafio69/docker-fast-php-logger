<?php

declare(strict_types=1);

namespace App\Logger;

/**
 * Validates file paths to ensure they stay within an allowed directory.
 */
class PathValidator
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    /**
     * Check whether a given file path is inside the allowed base directory.
     * Prevents directory traversal attacks.
     */
    public function isAllowed(string $filePath): bool
    {
        $real = realpath($filePath);
        $logReal = realpath($this->baseDir);

        // Normalize separators for Windows/WSL path compatibility
        $real = $real !== false ? str_replace('\\', '/', $real) : str_replace('\\', '/', $filePath);
        $logReal = $logReal !== false ? str_replace('\\', '/', $logReal) : str_replace('\\', '/', $this->baseDir);

        return str_starts_with($real, rtrim($logReal, '/') . '/');
    }
}
