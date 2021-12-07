<?php

namespace App\Controller;

use App\Entity\AnlageFile;
use App\Form\FileUpload\FileUploadFormType;
use App\Form\Owner\OwnerFormType;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    /**
     * @Route("/csv/upload/load", name="csv_upload_load")
     */
    public function load(Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper):Response
    {
        $form = $this->createForm(FileUploadFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked()) ) {
            dump("entro");
            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {

                dump($newFile = $uploaderHelper->uploadFile($uploadedFile, "csv",true));

            }

        }
        return $this->render('csv_upload/index.html.twig', [
            'controller_name' => 'CsvUploadController',
            'uploadForm' => $form->createView(),
        ]);
    }
}
