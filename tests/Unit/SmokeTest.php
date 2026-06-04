<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function testProjectAutoloaderWorks(): void
    {
        $this->assertTrue(class_exists(\App\Kernel::class));
    }
}
