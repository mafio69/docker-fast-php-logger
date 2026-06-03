<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Logger\PathValidator;
use PHPUnit\Framework\TestCase;

class PathValidatorTest extends TestCase
{
    private string $baseDir;
    private PathValidator $validator;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/path_validator_test_' . uniqid();
        mkdir($this->baseDir, 0777, true);
        mkdir($this->baseDir . '/subdir', 0777, true);
        file_put_contents($this->baseDir . '/test.log', 'content');
        file_put_contents($this->baseDir . '/subdir/deep.log', 'deep content');

        $this->validator = new PathValidator($this->baseDir);
    }

    protected function tearDown(): void
    {
        @unlink($this->baseDir . '/test.log');
        @unlink($this->baseDir . '/subdir/deep.log');
        @rmdir($this->baseDir . '/subdir');
        @rmdir($this->baseDir);
    }

    public function testAllowsFileInsideBaseDir(): void
    {
        $this->assertTrue($this->validator->isAllowed($this->baseDir . '/test.log'));
    }

    public function testAllowsFileInSubdirectory(): void
    {
        $this->assertTrue($this->validator->isAllowed($this->baseDir . '/subdir/deep.log'));
    }

    public function testDeniesFileOutsideBaseDir(): void
    {
        $this->assertFalse($this->validator->isAllowed('/etc/passwd'));
    }

    public function testDeniesDirectoryTraversal(): void
    {
        $this->assertFalse($this->validator->isAllowed($this->baseDir . '/../../../etc/passwd'));
    }

    public function testDeniesNonExistentFileOutside(): void
    {
        $this->assertFalse($this->validator->isAllowed('/tmp/nonexistent_file_xyz.log'));
    }

    public function testDeniesBaseDirItself(): void
    {
        // The base directory itself should not be "allowed" — only files inside it
        $this->assertFalse($this->validator->isAllowed($this->baseDir));
    }

    public function testDeniesParentDirectory(): void
    {
        $this->assertFalse($this->validator->isAllowed(dirname($this->baseDir)));
    }
}
