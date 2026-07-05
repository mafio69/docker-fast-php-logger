<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class McpController extends AbstractController
{
    #[Route('/mcp', name: 'app_mcp_dashboard')]
    public function index(): Response
    {
        return $this->render('mcp/index.html.twig', [
            'mcp_url' => 'http://localhost:8000',
        ]);
    }
}
