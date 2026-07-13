<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LogViewerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class LogController extends AbstractController
{
    private LogViewerService $logViewerService;

    public function __construct(LogViewerService $logViewerService)
    {
        $this->logViewerService = $logViewerService;
    }

    #[Route('/logs', name: 'app_logs')]
    public function index(Request $request): Response
    {
        if (!$this->logViewerService->isViewerAvailable()) {
            return new Response('Log viewer not found. Run: composer install', 500);
        }

        $type = $request->query->get('type'); // 'container' or 'host'
        try {
            $content = $this->logViewerService->renderViewer($type);
        } catch (Throwable $e) {
            return new Response('Error rendering logs: ' . $e->getMessage(), 500);
        }

        return new Response($content);
    }

    #[Route('/logs-assets/{type}/{assetPath}', name: 'app_logs_assets', requirements: ['type' => 'css|js', 'assetPath' => '.+'])]
    public function asset(string $type, string $assetPath): Response
    {
        $absolutePath = $this->logViewerService->getAssetAbsolutePath($type, $assetPath);
        if ($absolutePath === null) {
            return new Response('Asset not found', 404);
        }

        $contentType = $type === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        $content = file_get_contents($absolutePath);
        if ($content === false) {
            return new Response('Asset not readable', 500);
        }

        return new Response($content, 200, ['Content-Type' => $contentType]);
    }
}