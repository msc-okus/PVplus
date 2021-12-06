<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CsvUploadController extends AbstractController
{
    /**
     * @Route("/csv/upload", name="csv_upload")
     */
    public function index(): Response
    {
        return $this->render('csv_upload/index.html.twig', [
            'controller_name' => 'CsvUploadController',
        ]);
    }

}
