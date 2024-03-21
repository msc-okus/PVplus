<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Eigner;
use App\Form\User\UserFormType;
use App\Repository\UserRepository;
use App\Service\PvpDataService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;


class PvpTransferDataController extends AbstractController{

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
