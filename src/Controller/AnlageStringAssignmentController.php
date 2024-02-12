<?php

namespace App\Controller;

use App\Entity\AnlageStringAssignment;
use App\Form\Anlage\AnlageStringAssigmentType;
use App\Repository\AnlagenRepository;
use App\Repository\AnlageStringAssignmentRepository;
use App\Service\PdoService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PDO;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route(path: '/anlage/string/assignment/export/{anlId}', name: 'app_anlage_string_assignment_export')]
    public function acExport($anlId,AnlageStringAssignmentRepository $anlageStringAssignmentRepository,PdoService $pdo): Response
    {


        $dateX = new DateTime('2023-01-01 05:15:00');
        $dateY = new DateTime('2023-01-01 10:00:00');

        $assignments = $anlageStringAssignmentRepository->findBy(['anlage' => $anlId]);

        if (empty($assignments)) {
            $this->addFlash('error', 'No assignments found for the specified Anlage.');
            return $this->redirectToRoute('app_anlage_string_assignment_list');
        }

        $header = ['Station Nr', 'Inverter Nr', 'String Nr', 'Channel Nr', 'String Active', 'Channel Cat', 'Position', 'Tilt', 'Azimut', 'Panel Type', 'Inverter Type', 'Anlage'];

        $interval = new \DateInterval('PT15M');
        $periods = new \DatePeriod($dateX, $interval, $dateY->add($interval));


        // Construction de la requête SQL pour récupérer toutes les données nécessaires
        $sql = "
        SELECT `I_value`, `stamp`, `group_ac`, `wr_num`, `channel`
        FROM `db__string_pv_CX104`
        WHERE `stamp` BETWEEN :startDateTime AND :endDateTime
        AND `group_ac` IN (".implode(",", array_unique(array_map(function($assignment) {
                return (int) $assignment->getInverterNr();
            }, $assignments))).")
        AND `wr_num` IN (".implode(",", array_unique(array_map(function($assignment) {
                return (int) $assignment->getStringNr();
            }, $assignments))).")
        AND `channel` IN (".implode(",", array_unique(array_map(function($assignment) {
                return (string)((int)$assignment->getChannelNr());
            }, $assignments))).")
    ";


        // Préparation des paramètres pour la requête SQL
        $params = [
            ':startDateTime' => $dateX->format('Y-m-d H:i:s'),
            ':endDateTime' => $dateY->format('Y-m-d H:i:s'),
        ];

        // Exécution de la requête SQL
        $connection = $pdo->getPdoStringBoxes();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organisation des résultats de la requête en fonction de la structure de données souhaitée
        foreach ($periods as $dt) {
            $header[] = $dt->format('Y-m-d H:i:s');
        }

        $data = [$header];
        foreach ($assignments as $assignment) {
            $row = [
                $assignment->getStationNr(),
                $assignment->getInverterNr(),
                $assignment->getStringNr(),
                $assignment->getChannelNr(),
                $assignment->getStringActive(),
                $assignment->getChannelCat(),
                $assignment->getPosition(),
                $assignment->getTilt(),
                $assignment->getAzimut(),
                $assignment->getPanelType(),
                $assignment->getInverterType(),
                $anlId
            ];

            foreach ($periods as $dt) {
                $found = false;
                foreach ($results as $result) {

                    if ($result['group_ac'] === (int)$assignment->getInverterNr() &&
                        $result['wr_num'] === (int)$assignment->getStringNr() &&
                        (int)$result['channel'] === (int)$assignment->getChannelNr() &&
                        $result['stamp'] === $dt->format('Y-m-d H:i:s')) {
                        $row[] = $result['I_value'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $row[] = '';
                }
            }

            $data[] = $row;
        }

        $xlsx = SimpleXLSXGen::fromArray($data);
        $fileName = "ac_groups_{$anlId}.xlsx";


        // Instead of using downloadAsString, save the file temporarily
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $xlsx->saveAs($tempFile); // Save the XLSX file to a temporary file

        // Read the file content
        $xlsxContent = file_get_contents($tempFile);
        if ($xlsxContent === false) {
            throw new \Exception('Failed to read the Excel file');
        }

        // Prepare the response with the file content
        $response = new Response($xlsxContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        // Cleanup: Remove the temporary file
        unlink($tempFile);

        return $response;
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
            $request->query->getInt('page', 1), /* page number */
            25                                         /* limit per page */
        );

        return $this->render('anlage_string_assignment/list.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
