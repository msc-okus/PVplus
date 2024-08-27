<?php

namespace App\Controller;

use App\Service\PvpDataService;
use Com\Tecnick\Pdf\Base;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class PvpTransferDataController extends Base
{
    #[Route('/pvp_transfer', name: 'app_pvp_data_transfer')]
    public function transfer(PvpDataService $service): JsonResponse
    {

        $month=1;
        $startDate = date('Y-m-01 00:00:00', strtotime("2023-$month-01"));
        $endDate = date('Y-m-t 23:59:59', strtotime("2023-$month-01"));


        try {

            $service->transferData($startDate,$endDate );

            return $this->json(['success' => 'Data processed successfully']);
        } catch (\Exception $e) {

            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
