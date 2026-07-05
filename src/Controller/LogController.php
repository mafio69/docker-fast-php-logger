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
            $this->logViewerService->renderViewer($type);
        } catch (Throwable $e) {
            return new Response('Error rendering logs: ' . $e->getMessage(), 500);
        }
        
        return new Response('');
    }
}