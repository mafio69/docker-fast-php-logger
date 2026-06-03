<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class KernelTest extends TestCase
{
    public function testKernelClassExists(): void
    {
        $this->assertTrue(class_exists(Kernel::class));
    }

    public function testKernelHasRunMethod(): void
    {
        $reflection = new ReflectionClass(Kernel::class);
        $this->assertTrue($reflection->hasMethod('run'));
    }

    public function testRunMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(Kernel::class);
        $method = $reflection->getMethod('run');
        $this->assertTrue($method->isPublic());
    }

    public function testRunMethodReturnsVoid(): void
    {
        $reflection = new ReflectionClass(Kernel::class);
        $method = $reflection->getMethod('run');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }
}
