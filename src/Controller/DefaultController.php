<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_G4N')]
class DefaultController extends BaseController
{
    use G4NTrait;

    public function __construct()
    {

    }


    #[Route(path: '/admin/server-time', name: 'app_admin_server_time')]
    public function getServerTime(): Response
    {
        return $this->render('default/time.html.twig', [
            'serverDate' => date('Y-m-d H:i:s'),
        ]);
    }
}
