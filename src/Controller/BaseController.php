<?php

namespace App\Controller;
use App\Service\GetPdoService;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    protected function getUser(): ?User
    {
        return parent::getUser();
    }
}
