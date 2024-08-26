<?php

namespace App\Controller;

use App\Form\Anlage\AnlageConfigFormType;
use App\Form\Anlage\MultiUploadFormType;
use App\Repository\AnlageFileRepository;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class MediaStorageController extends BaseController
{
    #[Route('/media/storage', name: 'app_media_storage')]
    public function index(): Response
    {
        return $this->render('media_storage/index.html.twig', [
            'controller_name' => 'MediaStorageController',
        ]);
    }

    #[Route(path: '/media/list/{id}', name: 'app_media_list')]
    public function list($id,   Request $request, AnlagenRepository $anlagenRepo, PaginatorInterface $paginator, UploaderHelper $uploaderHelper, AnlageFileRepository $anlFileRepo): Response
    {
        $anlage = $anlagenRepo->findOneByIdAndJoin($id);

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            $session = $request->getSession();
            $pageSession = $session->get('page');
            $page = $request->query->getInt('page');

            if ($request->query->get('filtering') == 'filtered') {
                $request->query->set('filtering', 'non-filtered');

            } // we do this to reset the page if the user uses the filter

            if ($page == 0) {
                if ($pageSession == 0) {
                    $page = 1;
                } else {
                    $page = $pageSession;
                }
            }
            $anlage = $anlagenRepo->findOneByIdAndJoin($id);
            $name = $request->query->get('name');
            $type = $request->query->get('type');
            $sort = $request->query->get('sort', "");
            $direction = $request->query->get('direction', "");
            if ($direction === "") $direction = "desc";
            $queryBuilder = $anlFileRepo->getWithSearchQueryBuilder($anlage, $name, $type, $sort, $direction);
            $pagination = $paginator->paginate($queryBuilder, $page, 25);
            $pagination->setParam('sort', $sort);
            $pagination->setParam('direction', $direction);
            // check if we get no result
            if ($pagination->count() == 0) {
                $page = 1;
                $pagination = $paginator->paginate($queryBuilder, $page, 25);
                $pagination->setParam('sort', $sort);
                $pagination->setParam('direction', $direction);
            }
            $session->set('page', "$page");
            return $this->render('media_storage/_inc/_list.html.twig', [
                'pagination' => $pagination,
            ]);
        }
        else {
            $form = $this->createForm(MultiUploadFormType::class, $anlage, []);
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $extraFiles = $form['files']->getData();
                foreach ($extraFiles as $files) {
                    $uploaderHelper->uploadPlantDocumentation($files, $anlage->getEigner()->getFirma(), $anlage);
                }
            }
                $session = $request->getSession();
                $pageSession = $session->get('page');
                $page = $request->query->getInt('page');

                if ($request->query->get('filtering') == 'filtered') {
                    $request->query->set('filtering', 'non-filtered');

                } // we do this to reset the page if the user uses the filter

                if ($page == 0) {
                    if ($pageSession == 0) {
                        $page = 1;
                    } else {
                        $page = $pageSession;
                    }
                }
                $name = $request->query->get('name');
                $type = $request->query->get('type');
                $sort = $request->query->get('sort', "");
                $direction = $request->query->get('direction', "");
                if ($direction === "") $direction = "desc";
                $queryBuilder = $anlFileRepo->getWithSearchQueryBuilder($anlage, $name, $type, $sort, $direction);
                $pagination = $paginator->paginate($queryBuilder, $page, 25);
                $pagination->setParam('sort', $sort);
                $pagination->setParam('direction', $direction);
                // check if we get no result
                if ($pagination->count() == 0) {
                    $page = 1;
                    $pagination = $paginator->paginate($queryBuilder, $page, 25);
                    $pagination->setParam('sort', $sort);
                    $pagination->setParam('direction', $direction);
                }
                $session->set('page', "$page");

            return $this->render('media_storage/list.html.twig', [
                'id'         => $id,
                'pagination' => $pagination,
                'form'       => $form,
            ]);
        }
    }
    #[Route(path: '/media/delete/{id}', name: 'app_media_delete')]
    public function delete($id,   Request $request, PaginatorInterface $paginator, UploaderHelper $uploaderHelper, AnlageFileRepository $anlFileRepo, EntityManagerInterface $em): Response
    {
        $file = $anlFileRepo->findOneBy(['id' => $id]);
        if ($file){
            $uploaderHelper->deleteFile($file->getPath().$file->getFilename());
            $em->remove($file);
            $em->flush();
        }
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/media/download/{id}', name: 'app_media_download')]
    public function download($id, UploaderHelper $uploaderHelper, AnlageFileRepository $anlFileRepo) :Response
    {

        $anlageFile = $anlFileRepo->findOneBy(['id' => $id]);
        $response = new StreamedResponse(function() use ($anlageFile, $uploaderHelper) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $uploaderHelper->readStream($anlageFile->getPath().$anlageFile->getFilename());
            stream_copy_to_stream($fileStream, $outputStream);
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $anlageFile->getFilename()
        );
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }

}
