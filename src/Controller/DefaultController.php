<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(): Response
    {
        return new Response(
            '<html><body><h1>Strona główna</h1><p>Poszukaj czarnej belki na dole ekranu.</p></body></html>'
        );
    }
}
