<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlageFile;
use App\Entity\Eigner;
use App\Form\Owner\OwnerFormType;
use App\Repository\AnlageFileRepository;
use App\Repository\EignerRepository;
use App\Service\G4NSendMailService;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\AnlageFileUpload;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageFileUploadRepository;
use Michelf\MarkdownInterface;
use App\Api\PlantReferenceUploadApiModel;
use App\Entity\PlantReference;
use App\Form\FileUpload\FileUploadFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\File as FileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

class EignerController extends BaseController
{
    /**
     * @Route("/admin/owner/new", name="app_admin_owner_new")
     */
    public function new(EntityManagerInterface $em, Request $request): Response
    {
        $form = $this->createForm(OwnerFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            /** @var Eigner $owner */
            $owner = $form->getData();

            $em->persist($owner);
            $em->flush();

            $this->addFlash('success', 'New Owner created');

            return $this->redirectToRoute('app_admin_owner_list');

        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_owner_list');
        }


        return $this->render('owner/new.html.twig', [
            'ownerForm' => $form->createView(),
        ]);
    }


    /**
     * @Route("/admin/owner/list", name="app_admin_owner_list")
     */
    public function list(Request $request, PaginatorInterface $paginator, EignerRepository $ownerRepo): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $ownerRepo->getWithSearchQueryBuilder($q);

        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            20                                         /*limit per page*/
        );

        return $this->render('owner/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/owner/edit/{id}", name="app_admin_owner_edit")
     */
    public function edit($id, EntityManagerInterface $em, Request $request, EignerRepository $ownerRepo, UploaderHelper $uploaderHelper, AnlageFileRepository $RepositoryUpload): Response
    {
        $owner = $ownerRepo->find($id);
        $imageuploaded = $RepositoryUpload->findOneBy(['path' => $owner->getLogo()]);

        $form = $this->createForm(OwnerFormType::class, $owner);
        if($imageuploaded != null) {
            $isupload = 'yes';
        }
        else $isupload = 'no';
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            //upload image
            $upload = new AnlageFile();

            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {
                $isupload = "yes";
                $newFile = $uploaderHelper->uploadImage($uploadedFile, $id, "owner");
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];
                $uploadsPath ='uploads/'.UploaderHelper::EIGNER_LOGO.'/'.$id.'/'.$newFilename;
                $upload->setFilename($newFilename)
                    ->setMimeType($mimeType)
                    ->setPath($uploadsPath)
                    ->setPlant(null)
                    ->setStamp(date('Y-m-d H:i:s'));

                $em->persist($upload);
                $em->flush();

                $owner->setLogo($uploadsPath);
            }
                //the rest
            $em->persist($owner);
            $em->flush();
            $imageuploaded = $RepositoryUpload->findOneBy(['path' => $owner->getLogo()]);
            if($form->get('save')->isClicked()) {
                return $this->render('owner/edit.html.twig', [
                    'ownerForm' => $form->createView(),
                    'isupload' => $isupload,
                    'imageuploadet' => $imageuploaded->getPath()
                ]);
            }
            if ($form->get('saveclose')->isClicked()) {
                return $this->redirectToRoute('app_admin_owner_list');
            }
        }

        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            return $this->redirectToRoute('app_admin_owner_list');
        }
        if($imageuploaded != null)
        return $this->render('owner/edit.html.twig', [
            'ownerForm' => $form->createView(),
            'fileUploadForm' => $form->createView(),
            'isupload' => $isupload,
            'imageuploadet' => $imageuploaded->getPath()
        ]);
        else         return $this->render('owner/edit.html.twig', [
            'ownerForm' => $form->createView(),
            'fileUploadForm' => $form->createView(),
            'isupload' => $isupload,
        ]);
    }
}

