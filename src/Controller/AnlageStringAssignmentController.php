<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Entity\AnlageStringAssignment;


use App\Entity\Eigner;
use App\Form\Anlage\AnlageStringAssigmentCreateType;
use App\Form\Anlage\AnlageStringAssigmentType;


use App\Form\Anlage\AnlageStringAssigmentUploadType;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\AnlageStringAssigmentService;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

use League\Flysystem\Filesystem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Shuchkin\SimpleXLSX;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\LogMessagesService;



class AnlageStringAssignmentController extends AbstractController
{

    #[Route(path: '/analysis/list', name: 'app_analysis_list')]
    public function listExport(Request $request, EntityManagerInterface $entityManager,PaginatorInterface $paginator,AnlageStringAssigmentService $anlageStringAssigmentService, AnlagenRepository $anlagenRepository,ReportsRepository $reportsRepository,Security $security,LogMessagesService $logMessages, MessageBusInterface $messageBus): Response
    {


        $grantedPlantList = $this->getUser()->getGrantedArray();
        $eigners = [];
        /** @var Eigner $eigner */
        foreach ($this->getUser()->getEigners()->toArray() as $eigner) {
            $eigners[] = $eigner->getId();
        }

        $assignments = $entityManager->getRepository(AnlageStringAssignment::class)->findAll();


        $anlageWithAssignments = [];
        foreach ($assignments as $assignment) {
            $anlageWithAssignments[$assignment->getAnlage()->getAnlId()] = true;
        }
        $anlagen = $anlagenRepository->getOwner($eigners, $grantedPlantList)->getQuery()->getResult();

        $anlagenChoicesUpload=[];
        $anlagenChoicesGenerate=[];
        foreach ($anlagen as $anlage) {

            $lastUploadDate = $anlage->getLastAnlageStringAssigmentUpload();
            $dateStr = $lastUploadDate ? $lastUploadDate->format('d-m-Y H:i:s') : ' never';
            $hasAssignments = isset($anlageWithAssignments[$anlage->getAnlId()]);
           if($hasAssignments){
               $arrow =  '🔵' ;
               $anlagenChoicesGenerate[$anlage->getAnlName()] = $anlage;
           }else{
               $arrow =  '';
           }

            $x= sprintf("%s (%s) - Last upload: %s  %s", $anlage->getAnlName(),$anlage->getAnlId(), $dateStr, $arrow);
            $anlagenChoicesUpload[$x] = $anlage;

        }


        $createForm = $this->createForm(AnlageStringAssigmentCreateType::class, null, [
            'anlagen_choices' => $anlagenChoicesGenerate,
        ]);

        $createForm->handleRequest($request);
        if ($createForm->isSubmitted() && $createForm->isValid()) {

            $anlage = $createForm['anlage']->getData();
            $anlageId = $anlage->getAnlagenId();
            $currentUserName = $security->getUser()->getEmail();
            $month = $createForm['month']->getData();
            $year = $createForm['year']->getData();
            $publicDirectory = './excel/anlagestring/';
            $uid = $this->getUser()->getUserId();

            $job = 'Excel file is  generating for ' . $month . $year;
            $job .= " - " . $this->getUser()->getname();
            $logId = $logMessages->writeNewEntry($anlage, 'AnlageStringAssignment', $job, $uid);

          //  $anlageStringAssigmentService->exportMontly((int)$anlageId,(int)$year,(int)$month,$currentUserName,$publicDirectory,$logId);

            $message = new \App\Message\Command\AnlageStringAssignment((int)$anlageId,(int)$year,(int)$month,$currentUserName,$publicDirectory,$logId);
            $messageBus->dispatch($message);


            return new Response(null, \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
        }

        $uploadForm = $this->createForm(AnlageStringAssigmentUploadType::class, null, [
            'anlagen_choices' => $anlagenChoicesUpload,
        ]);
        $uploadForm->handleRequest($request);


        if ($uploadForm->isSubmitted() && $uploadForm->isValid()) {
                $file = $uploadForm['file']->getData();
                $anlage = $uploadForm['anlage']->getData();



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


                        return $this->redirectToRoute('app_analysis_list');

                    }

                }
            }


        $searchyear = $searchmonth =   $anlage = '';



        $queryBuilder= $reportsRepository->getWithSearchQueryBuilderAnlageString($anlage, $searchmonth, $searchyear);

        $reports = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('anlage_string_assignment/tab_report.html.twig', [
                'reports' => $reports,
                'searchyear' => $searchyear,
                'month'      => $searchmonth,
                'anlage'     => $anlage,
            ]);
        }

        return $this->render('anlage_string_assignment/list_report.html.twig', [
            'createForm' => $createForm->createView(),
            'uploadForm' => $uploadForm->createView(),
            'reports'=> $reports,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'anlage'     => $anlage,
            'anlagen'     => $anlagen,
        ]);
    }

    #[Route(path: '/analysis/list/search', name: 'app_analysis_list_search', methods: ['GET', 'POST'])]
    public function search(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository): Response
    {
        $anlage = $request->query->get('anlage');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');
        $page = $request->query->getInt('page', 1);

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilderAnlageString($anlage, $searchmonth, $searchyear);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );
        return $this->render('anlage_string_assignment/tab_report.html.twig', [
            'reports' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'anlage'     => $anlage,
        ]);
    }



    #[Route('/analysis/download/{fileName}', name: 'app_analysis_download_file')]
    public function downloadFile($fileName,Filesystem $fileSystemFtp): Response
    {
        $publicDirectory='./excel/anlagestring/';
        $filePath = $publicDirectory . urldecode($fileName);


        if (!$fileSystemFtp->fileExists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        // Get the contents of the file from the FTP filesystem
        $fileStream = $fileSystemFtp->readStream($filePath);

        // Create a StreamedResponse to output the file contents
        $response = new StreamedResponse(function () use ($fileStream) {
            fpassthru($fileStream);
            fclose($fileStream);
        });

        // Set the response headers for file download
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');

        return $response;
    }


    #[Route('/analysis/delete', name: 'app_analysis_delete_file')]
    public function deleteFile( EntityManagerInterface $entityManager,Filesystem $fileSystemFtp,Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository): Response
    {

        $reportId=  $request->query->get('reportId');
        $fileName=  $request->query->get('filename');
        $anlage = $request->query->get('anlage');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');


        $decodeFilename = urldecode($fileName);
        $anlagenReportRepository = $entityManager->getRepository(AnlagenReports::class);
        $anlagenReport = $anlagenReportRepository->find($reportId);
        if (!$anlagenReport) {
            throw $this->createNotFoundException('AnlagenReport not found');
        }


        $publicDirectory = './excel/anlagestring/';
        $filePath = $publicDirectory . $decodeFilename;

        if (!$fileSystemFtp->fileExists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

            $fileSystemFtp->delete($decodeFilename);
            $entityManager->remove($anlagenReport);
            $entityManager->flush();


        $queryBuilder = $reportsRepository->getWithSearchQueryBuilderAnlageString($anlage, $searchmonth, $searchyear);
        $page = $request->query->getInt('page', 1);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );
        return $this->render('anlage_string_assignment/tab_report.html.twig', [
            'reports' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'anlage'     => $anlage,
        ]);




    }

}

