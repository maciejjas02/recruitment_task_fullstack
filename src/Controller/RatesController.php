<?php

namespace App\Controller;

use App\Service\RatesService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

final class RatesController
{
    public function __construct(private RatesService $svc) {}

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function homepage(): RedirectResponse
    {
        return new RedirectResponse('/app.html');
    }

    #[Route('/api/rates', name: 'rates_list', methods: ['GET'])]
    public function list(Request $req): JsonResponse
    {
        $date = $req->query->get('date'); 
        $rows = $this->svc->getRates($date);

        $res = new JsonResponse($rows);
        $res->headers->set('Cache-Control', 'public, max-age=60, s-maxage=60');
        return $res;
    }

    #[Route('/api/rates/{code}/history', name: 'rates_history', methods: ['GET'])]
    public function history(string $code, Request $req): JsonResponse
    {
        $startDate = $req->query->get('startDate');
        $endDate = $req->query->get('endDate');
        $date = $req->query->get('date'); 
        
        if ($startDate && $endDate) {
            $points = $this->svc->getHistoryRange($code, $startDate, $endDate);
        } else {
            $points = $this->svc->getHistory($code, $date);
        }

        $res = new JsonResponse([
            'code'   => $code,
            'points' => $points,
        ]);
        $res->headers->set('Cache-Control', 'public, max-age=60, s-maxage=60');
        return $res;
    }
}
