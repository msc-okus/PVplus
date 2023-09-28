<?php

namespace App\Controller;

use App\Entity\AnlageFile;
use App\Entity\Eigner;
use App\Form\Owner\OwnerFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlageFileRepository;
use App\Repository\EignerRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use League\Flysystem\Filesystem;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_G4N')]
class EignerController extends BaseController
{
    use G4NTrait;
    #[Route(path: '/admin/owner/new', name: 'app_admin_owner_new')]
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
            'ownerForm' => $form,
            'isupload' => '',
        ]);
    }


    #[Route(path: '/admin/owner/list', name: 'app_admin_owner_list')]
    public function list(Request $request, PaginatorInterface $paginator, EignerRepository $ownerRepo): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $ownerRepo->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            20                                         /* limit per page */
        );

        return $this->render('owner/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/admin/owner/edit/{id}', name: 'app_admin_owner_edit')]
    public function edit($id, EntityManagerInterface $em, Request $request, EignerRepository $ownerRepo, UploaderHelper $uploaderHelper, AnlageFileRepository $RepositoryUpload, Filesystem $fileSystemFtp, Filesystem $filesystem): Response
    {
        $tempFile = '';
        $owner = $ownerRepo->find($id);
        $imageuploaded = $RepositoryUpload->findOneBy(['path' => $owner->getLogo()]);
        $form = $this->createForm(OwnerFormType::class, $owner);
        if ($imageuploaded != null) {
            $isupload = 'yes';
            if ($fileSystemFtp->fileExists($imageuploaded->getPath())) {
                $tempFile = self::makeTempFiles([$fileSystemFtp->read($imageuploaded->getPath())], $filesystem)[0];
            }
            else $isupload = 'no';
        } else {
            $isupload = 'no';
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && ($form->get('save')->isClicked() || $form->get('saveclose')->isClicked())) {
            // upload image
            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {
                $upload = new AnlageFile();
                $isupload = 'yes';
                $newFile = $uploaderHelper->uploadImageSFTP($uploadedFile,$owner->getFirma(), '' , 'owner');
                $newFilename = $newFile['newFilename'];
                $mimeType = $newFile['mimeType'];
                $uploadsPath = $newFile['path'];
                $upload->setFilename($newFilename)
                    ->setMimeType($mimeType)
                    ->setPath($uploadsPath)
                    ->setPlant(null)
                    ->setStamp(date('Y-m-d H:i:s'));

                $em->persist($upload);
                $em->flush();

                $owner->setLogo($uploadsPath);
                //here we update the pic
                $tempFile = self::makeTempFiles([$fileSystemFtp->read($uploadsPath)], $filesystem)[0];
            }
            // the rest
            $em->persist($owner);
            $em->flush();
            if ($form->get('save')->isClicked()) {
                $response = $this->render('owner/edit.html.twig', [
                    'ownerForm' => $form,
                    'isupload' => $isupload,
                    'imageuploadet' => $tempFile,
                ]);
            }
            if ($form->get('saveclose')->isClicked()) {
                $response = $this->redirectToRoute('app_admin_owner_list');
            }
        }
        if ($form->isSubmitted() && $form->get('close')->isClicked()) {
            $this->addFlash('warning', 'Canceled. No data was saved.');

            $response = $this->redirectToRoute('app_admin_owner_list');
        }

        if (!$form->isSubmitted() || $form->isValid()) $response = $this->render('owner/edit.html.twig', [
                'ownerForm' => $form,
                'fileUploadForm' => $form,
                'isupload' => $isupload,
                'imageuploadet' => $tempFile,
            ]);
        return $response;
    }
}
