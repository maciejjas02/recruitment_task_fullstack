<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    // Serwuje stronę SPA (React). Na ten widok kierujemy wszystkie ścieżki poza /api/*
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    // Prosta “żyję” dla backendu – jeśli używasz w routes.yaml
    public function setupCheck(): Response
    {
        return $this->json(['ok' => true]);
    }
}
