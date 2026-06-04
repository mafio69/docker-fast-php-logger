<?php

namespace App\Controller;

use App\Service\LogViewerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $this->logViewerService->renderViewer($type);
        
        return new Response('');
    }
}