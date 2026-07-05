<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MdViewerController extends AbstractController
{
    #[Route('/mdviewer', name: 'app_mdviewer')]
    public function index(): Response
    {
        return $this->render('mdviewer/index.html.twig');
    }
}