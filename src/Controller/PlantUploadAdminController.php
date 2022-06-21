<?php

namespace App\Controller;

use App\Entity\AnlageFileUpload;
use App\Api\PlantReferenceUploadApiModel;
use App\Entity\Anlage;
use App\Entity\PlantReference;
use App\Form\FileUpload\FileUploadFormType;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class PlantUploadAdminController extends BaseController
{
    private string $uploadsPath;

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    #[Route(path: '/admin/upload/{id}', name: 'upload_test')]
    public function temporaryUploadAction($id, Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface $entityManager)
    {
        #$anlage = new AnlageFileUpload();
        $repositoryUpload = $entityManager->getRepository(AnlageFileUpload::class);
        $repositoryAnlage = $entityManager->getRepository(Anlage::class);
        //create Form
        /** @var UploadedFile $uploadedFile */
        $form = $this->createForm(FileUploadFormType::class);
        $form->handleRequest($request);
        $filesInDB = $repositoryUpload->findBy(['plant_id' => $id]);
        $anlage = $repositoryAnlage->findIdLike([$id]);
        $isupload = '';
        if($form->isSubmitted()){
            $upload = new AnlageFileUpload();

            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {

                $newFile = $uploaderHelper->uploadPlantImage($uploadedFile, $id);
                $uploadsPath = substr ($this->uploadsPath,strpos($this->uploadsPath, '/uploads')).'/'.UploaderHelper::PLANT_IMAGE.'/'.$id;
                $originalFilename = $newFile['originalFilename'];
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];

                $isupload = 'yes';

                $upload->setStamp(date_create(date('Y-m-d H:i:s')))
                ->setUploadPath($uploadsPath)
                    ->setCreatedAt(date_create(date('Y-m-d H:i:s')))
                    ->setCreatedBy('John Wayne')
                    ->setFilename($newFilename)
                    ->setMimeType($mimeType)
                    ->setOriginalFileName($originalFilename)
                    ->setPlantId($anlage)
                ;
                $entityManager->persist($upload);
                $entityManager->flush();

                $imageuploadet = $repositoryUpload->findOneBy(['filename' => $newFilename]);

                return $this->render('fileUpload/fileupload.html.twig', [
                    'isupload' => $isupload,
                    'imageuploadet' => $imageuploadet
                ]);
            }
    }
        return $this->render('fileUpload/fileupload.html.twig', [
            'fileUploadForm' => $form->createView(),
            'isupload' => $isupload,
            'fileseindb' => $filesInDB
        ]);
    }
}
