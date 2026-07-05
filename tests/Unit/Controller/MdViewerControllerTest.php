<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\MdViewerController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;

/**
 * @covers \App\Controller\MdViewerController
 */
class MdViewerControllerTest extends TestCase
{
    private MdViewerController $controller;

    protected function setUp(): void
    {
        $this->controller = new MdViewerController();
        
        // Mock container with twig to allow render()
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('mocked html content');
        
        $container = new Container();
        $container->set('twig', $twig);
        
        $this->controller->setContainer($container);
    }

    public function testIndexReturnsResponse(): void
    {
        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('mocked html content', $response->getContent());
    }
}
