<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Entity\AnlageStringAssignment;
use App\Form\Anlage\AnlageStringAssigmentType;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageStringAssignmentRepository;
use App\Repository\ReportsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shuchkin\SimpleXLSX;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AnalysisController extends AbstractController
{
    #[Route('/analysis/upload', name: 'app_analysis_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request,EntityManagerInterface $entityManager): Response
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

            if ($file == null) dd("no file");
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
                    return $this->redirectToRoute('app_analysis_list');

                }

                $this->addFlash('error', 'Error');
            }
        }
        $this->addFlash('error', 'Error');
        return $this->redirectToRoute('app_analysis_list');
    }

    #[Route(path: '/analysis', name: 'app_analysis_list')]
    public function list(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository, AnlagenRepository $anlagenRepo, AnlageStringAssignmentRepository $stringRepo): Response
    {
        $assignments = $stringRepo->findAll();
        $anlageWithAssignments = [];
        foreach ($assignments as $assignment) {
            $anlageWithAssignments[$assignment->getAnlage()->getAnlId()] = true;
        }

        $form = $this->createForm(AnlageStringAssigmentType::class,null, [
            'anlageWithAssignments' => $anlageWithAssignments,
        ]);
        $searchyear = date('Y');
        $searchstatus = $searchtype = $searchmonth = $anlage = '';
        $queryBuilder = $reportsRepository->getWithSearchQueryBuilderAnalysis($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        if ($request->query->get('ajax') || $request->isXmlHttpRequest()) {
            return $this->render('reporting/_inc/_listReports.html.twig', [
                'pagination' => $pagination,
                'searchyear' => $searchyear,
                'month'      => $searchmonth,
                'type'       => $searchtype,
                'status'     => $searchstatus,
                'anlage'     => $anlage,
            ]);
        }

        return $this->render('/analysis/list.html.twig',[
            'form' => $form->createView(),
            'pagination' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }
    #[Route(path: '/analysis/search', name: 'app_analysis_search', methods: ['GET', 'POST'])]
    public function search(Request $request, PaginatorInterface $paginator, ReportsRepository $reportsRepository): Response
    {
        $anlage = $request->query->get('anlage');
        $searchstatus = $request->query->get('searchstatus');
        $searchtype = $request->query->get('searchtype');
        $searchmonth = $request->query->get('searchmonth');
        $searchyear = $request->query->get('searchyear');
        $page = $request->query->getInt('page', 1);

        $queryBuilder = $reportsRepository->getWithSearchQueryBuilderAnalysis($anlage, $searchstatus, $searchtype, $searchmonth, $searchyear);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            20
        );
        return $this->render('analysis/_inc/_listReports.html.twig', [
            'pagination' => $pagination,
            'searchyear' => $searchyear,
            'month'      => $searchmonth,
            'type'       => $searchtype,
            'status'     => $searchstatus,
            'anlage'     => $anlage,
        ]);
    }
    #[Route('/analysis/download/{id}', name: 'app_analysis_download')]
    public function downloadFile($id, ReportsRepository $reportsRepo): Response
    {

        $report = $reportsRepo->find(['id'=>$id]);
        $publicDirectory = $this->getParameter('kernel.project_dir') . "/public/download/anlageString";
        $filePath = $publicDirectory. "/" . $report->getFile(); // we must change the creation in the future to store the full path

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    #[Route(path: '/analysis/delete/{id}', name: 'app_analysis_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete($id, ReportsRepository $reportsRepository, EntityManagerInterface $em): Response
    {
        $anlagenReport = $reportsRepository->find($id);
        if (!$anlagenReport) {
            throw $this->createNotFoundException('Report not found');
        }

        $publicDirectory = $this->getParameter('kernel.project_dir') . "/public/download/anlageString";
        $filePath = $publicDirectory. "/" . $anlagenReport->getFile(); // we must change the creation in the future to store the full path

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        if (unlink($filePath)) {
            $em->remove($anlagenReport);
            $em->flush();
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
        else return new Response('Failed to delete the file', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
 //TODO: CHANGE THIS LATER TO THE PROPER WAY OF DOING IT
    private function generateAndSaveExcelFile($spreadsheet, $anlId, $month, $year,$currentUserName,$publicDirectory, AnlagenRepository $anlRepo)
    {
        $writer = new Xlsx($spreadsheet);
        $fileName = "string_analysis_{$anlId}_{$month}_{$year}.xlsx";
        $filePath = $publicDirectory . '/' . $fileName;
        $anlage = $anlRepo->findOneBy(['anlId' => $anlId]);
        $anlagenReport= new AnlagenReports();
        $anlagenReport->setAnlage($anlage);
        $anlagenReport->setEigner($anlage->getEigner());
        $anlagenReport->setReportType('string-analyse');
        $anlagenReport->setMonth($month);
        $anlagenReport->setYear($year);
        $anlagenReport->setFile($filePath);
        $anlagenReport->setRawReport('');
        $anlagenReport->setCreatedBy($currentUserName);


        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $writer->save($filePath);

        $this->entityManager->persist($anlagenReport);
        $this->entityManager->flush();

    }
}
