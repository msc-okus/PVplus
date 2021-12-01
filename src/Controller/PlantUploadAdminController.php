<?php

namespace App\Controller;

use App\Entity\AnlageFile;
use App\Entity\AnlageFileUpload;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageFileUploadRepository;
use Michelf\MarkdownInterface;
use App\Api\PlantReferenceUploadApiModel;
use App\Entity\Anlage;
use App\Entity\PlantReference;
use App\Form\FileUpload\FileUploadFormType;
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
    public function temporaryUploadAction($id, Request $request, UploaderHelper $uploaderHelper, EntityManagerInterface $entityManager)
    {
        #$anlage = new AnlageFileUpload();
        $repositoryUpload = $entityManager->getRepository(AnlageFile::class);
        $repositoryAnlage = $entityManager->getRepository(Anlage::class);
        //create Form
        /** @var UploadedFile $uploadedFile */
        $form = $this->createForm(FileUploadFormType::class);
        $form->handleRequest($request);

        $filesInDB = $repositoryUpload->findBy(['plant_id' => $id]);

        $anlage = $repositoryAnlage->findIdLike([$id])[0];

        $isupload = '';
        if($form->isSubmitted()){
            $upload = new AnlageFile();

            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {

                $newFile = $uploaderHelper->uploadPlantImage($uploadedFile, $id);
                $uploadsPath = substr ($this->uploadsPath,strpos($this->uploadsPath, '/uploads')).'/'.UploaderHelper::PLANT_IMAGE.'/'.$id;
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];

                $isupload = 'yes';
                $upload ->setFilename($newFilename)
                        ->setNimeType($mimeType)
                        ->setPath($uploadsPath)
                        ->setPlant($anlage)
                        ->setStamp(date_create(date('Y-m-d H:i:s')));

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
