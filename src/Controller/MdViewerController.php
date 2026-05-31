<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MdViewerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MdViewerController
{
    private Environment $twig;

    public function __construct(
        private readonly MdViewerService $service,
    ) {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, ['cache' => false, 'debug' => true]);
    }

    #[Route('/mdviewer', methods: ['GET'])]
    #[Route('/mdviewer/', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $html = $this->twig->render('mdviewer/index.html.twig');

            return new Response($html);
        } catch (\Throwable $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/api/mdviewer/data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        try {
            $result = $this->service->getData(
                search: $request->query->get('search'),
                status: $request->query->get('status'),
                category: $request->query->get('category'),
                page: (int) $request->query->get('page', '1'),
                perPage: (int) $request->query->get('perPage', '25'),
                sortCol: $request->query->get('sort', 'id'),
                sortDir: $request->query->get('dir', 'asc'),
            );

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
