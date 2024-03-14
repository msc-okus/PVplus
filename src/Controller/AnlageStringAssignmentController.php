<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Entity\AnlageStringAssignment;


use App\Form\Anlage\AnlageStringAssigmentType;


use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\AnlageStringAssigmentService;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Shuchkin\SimpleXLSX;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\LogMessagesService;



class AnlageStringAssignmentController extends AbstractController
{
    #[Route('/anlage/string/assignment/upload', name: 'app_anlage_string_assignment_upload')]
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {

        $assignments = $entityManager->getRepository(AnlageStringAssignment::class)->findAll();


        $anlageWithAssignments = [];
        foreach ($assignments as $assignment) {
            $anlageWithAssignments[$assignment->getAnlage()->getAnlId()] = true;
        }

        $form = $this->createForm(AnlageStringAssigmentType::class,null, [
            'anlageWithAssignments' => $anlageWithAssignments,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form['file']->getData();
            $anlage = $form['anlage']->getData();



            if ($anlage) {
                $existingAssignments = $entityManager->getRepository(AnlageStringAssignment::class)->findBy(['anlage' => $anlage]);
                foreach ($existingAssignments as $assignment) {
                    $entityManager->remove($assignment);
                }
                $entityManager->flush();

                $anlage->setLastAnlageStringAssigmentUpload(new \DateTime());
                $entityManager->persist($anlage);
                $entityManager->flush();
            }

            if ($file) {
                $xlsx = SimpleXLSX::parse($file->getRealPath());
                if ($xlsx) {
                    $firstRow = true;
                    foreach ($xlsx->rows() as $row) {

                        if ($firstRow) {
                            $firstRow = false;
                            continue;
                        }
                        $assignment = new AnlageStringAssignment();
                        $assignment->setStationNr($row[0]);
                        $assignment->setInverterNr($row[1]);
                        $assignment->setStringNr($row[2]);
                        $assignment->setChannelNr($row[3]);
                        $assignment->setStringActive($row[4]);
                        $assignment->setChannelCat($row[5]);
                        $assignment->setPosition($row[6]);
                        $assignment->setTilt($row[7]);
                        $assignment->setAzimut($row[8]);
                        $assignment->setPanelType($row[9]);
                        $assignment->setInverterType($row[10]);
                        $assignment->setAnlage($anlage);
                        $entityManager->persist($assignment);
                    }
                    $entityManager->flush();

                    $this->addFlash('success', 'Success');
                    return $this->redirectToRoute('app_anlage_string_assignment_upload');

                }

                $this->addFlash('error', 'Error');
            }
        }



        return $this->render('anlage_string_assignment/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/anlage/string/assignment/anlage/list', name: 'app_anlage_string_assignment_list')]
    public function listExport(Request $request, PaginatorInterface $paginator, AnlagenRepository $anlagenRepository): Response
    {


        $q = $request->query->get('qp');
        if ($request->query->get('search') == 'yes' && $q == '') {
            $request->getSession()->set('qp', '');
        }
        if ($q) {
            $request->getSession()->set('qp', $q);
        }
        if ($q == '' && $request->getSession()->get('qp') != '') {
            $q = $request->getSession()->get('qp');
            $request->query->set('qp', $q);
        }
        $queryBuilder = $anlagenRepository->getWithSearchQueryBuilder($q);
        $pagination = $paginator->paginate(
            $queryBuilder, /* query NOT result */
            $request->query->getInt('page', 1),
            25
        );

        return $this->render('anlage_string_assignment/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/anlage/string/assignment/monthly/export/{anlId}', name: 'app_anlage_string_assignment_monthly_export')]
    public function acExportMonthly($anlId,Request $request,Security $security, AnlageStringAssigmentService $anlageStringAssigmentService,LogMessagesService $logMessages, MessageBusInterface $messageBus, AnlagenRepository $anlagenRepo): Response
    {
        $anlage = $anlagenRepo->findOneBy(['anlId' => $anlId]);
        $anlageId = $anlage->getAnlagenId();
        $currentUserName = $security->getUser()->getEmail();
        $year = (int)$request->query->get('year');
        $month = (int)$request->query->get('month');
        $publicDirectory = $this->getParameter('kernel.project_dir') . "/public/download/anlageString";
        $uid = $this->getUser()->getUserId();

        $job = 'Excel file is  generating for ' . $month . $year;
        $job .= " - " . $this->getUser()->getname();
        $logId = $logMessages->writeNewEntry($anlage, 'AnlageStringAssignment', $job, $uid);

        $message = new \App\Message\Command\AnlageStringAssignment((int)$anlageId,$year,$month,$currentUserName,$publicDirectory,$logId);
        $messageBus->dispatch($message);


        return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }


    #[Route(path: '/anlage/string/assignment/monthly/export/list/{anlId}', name: 'app_anlage_string_assignment_monthly_export_list')]
    public function acExportMonthlyList($anlId,ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepository): Response
    {


        $anlage = $anlagenRepository->findOneBy(['anlId' => $anlId]);
        $reportType='string-analyse';
        $reports= $reportsRepository->findBy(['reportType'=>$reportType,'anlage' => $anlage,]);


        return $this->render('anlage_string_assignment/export_list.html.twig', [
            'reports' => $reports
        ]);
    }

    #[Route('/anlage/string/assignment/monthly/export/download/{fileName}', name: 'app_anlage_string_assignment_monthly_export_download_file')]
    public function downloadFile($fileName): Response
    {
        $publicDirectory = $this->getParameter('kernel.project_dir') . "/public/download/anlageString";
        $filePath = $publicDirectory . '/' . urldecode($fileName);


        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }


    #[Route('/anlage/string/assignment/monthly/export/delete/{fileName}/{id}', name: 'app_anlage_string_assignment_monthly_export_delete_file')]
    public function deleteFile( $fileName, $id,EntityManagerInterface $entityManager): Response
    {
        $decodeFilename = urldecode($fileName);


        $anlagenReportRepository = $entityManager->getRepository(AnlagenReports::class);


        $anlagenReport = $anlagenReportRepository->find($id);

        if (!$anlagenReport) {
            throw $this->createNotFoundException('AnlagenReport not found');
        }


        $publicDirectory = $this->getParameter('kernel.project_dir') . "/public/download/anlageString";
        $filePath = $publicDirectory . '/' . $decodeFilename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        if (unlink($filePath)) {

            $entityManager->remove($anlagenReport);
            $entityManager->flush();


            $anlId = explode('_', $decodeFilename)[1];
            return $this->redirectToRoute('app_anlage_string_assignment_monthly_export_list', ['anlId' => $anlId]);
        }

        return new Response('Failed to delete the file', Response::HTTP_INTERNAL_SERVER_ERROR);
    }



}

