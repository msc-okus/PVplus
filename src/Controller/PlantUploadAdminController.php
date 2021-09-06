<?php

namespace App\Controller;

use App\Entity\AnlageFileUpload;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageFileUploadRepository;
use Michelf\MarkdownInterface;
use App\Api\PlantReferenceUploadApiModel;
use App\Entity\Anlage;
use App\Entity\PlantReference;
use App\Form\FileUpload\fileUploadFormType;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Form\FormView;
use App\Service\MarkdownHelper;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use App\Entity\TimesConfig;
use App\Repository\TimesConfigRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;

class PlantUploadAdminController extends BaseController
{
    private $uploadsPath;


    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    /**
     * @Route("/admin/upload/{id}", name="upload_test")
     */
    public function temporaryUploadAction($id, Request $request, UploaderHelper $uploaderHelper, AnlagenRepository $anlagenRepository, EntityManagerInterface $entityManager)
    {
        #$anlage = new AnlageFileUpload();
        $repositoryUpload = $entityManager->getRepository(AnlageFileUpload::class);
        $repositoryAnlage = $entityManager->getRepository(Anlage::class);
        //create Form
        /** @var UploadedFile $uploadedFile */
        $form = $this->createForm(fileUploadFormType::class);
        $form->handleRequest($request);

        $filesInDB = $repositoryUpload->findAll(['plant_id' => $id]);

        $anlage = $repositoryAnlage->findOneBy(['anlId' => $id]);
        #dd(substr ($this->uploadsPath,strpos($this->uploadsPath, '/uploads')));


        $isupload = '';
        if($form->isSubmitted()){
            $upload = new AnlageFileUpload();

            $uploadedFile = $form['imageFile']->getData();
            #dd($uploadedFile);
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
            #dd($newFilename);
    }

        return $this->render('fileUpload/fileupload.html.twig', [
            'fileUploadForm' => $form->createView(),
            'isupload' => $isupload,
            'fileseindb' => $filesInDB
        ]);
    }
}
