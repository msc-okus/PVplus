<?php

namespace App\Controller;

use App\Form\Import\ImportEGridFormType;
use App\Form\Import\ImportPvSystFormType;
use App\Form\ImportTools\ImportToolsFormType;
use App\Form\Model\ImportToolsModel;
use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Message\Command\ImportData;
use App\Repository\AnlagenRepository;
use App\Service\Import\ImportTicketFBExcel;
use App\Service\Import\PvSystImportService;
use App\Service\ImportService;
use App\Service\LogMessagesService;
use App\Service\PdoService;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Gedmo\Sluggable\Util\Urlizer;
use JsonException;
use Shuchkin\SimpleXLSX;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class ImportToolsController extends BaseController
{
    use ImportFunctionsTrait;
    use G4NTrait;

    /**
     * VCOM Import per Import Tools Menü.
     *
     * @param Request $request
     * @param MessageBusInterface $messageBus
     * @param LogMessagesService $logMessages
     * @param AnlagenRepository $anlagenRepo
     * @param EntityManagerInterface $entityManagerInterface
     * @param ImportService $importService
     * @return Response
     * @throws \Exception
     */
    #[Route('admin/import/tools', name: 'app_admin_import_tools')]
    public function importTools(Request $request, MessageBusInterface $messageBus, LogMessagesService $logMessages, AnlagenRepository $anlagenRepo, EntityManagerInterface $entityManagerInterface, ImportService $importService): Response
    {

        //Wenn der Import aus dem Backend angestoßen wird
        $form = $this->createForm(ImportToolsFormType::class);
        $form->handleRequest($request);

        $output = '';
        $start = true;

        // Wenn Calc gelickt wird mache dies:&& $form->get('calc')->isClicked() $form->isSubmitted() &&
        if ($form->isSubmitted() && $form->isValid() && $request->getMethod() == 'POST') {

            /* @var ImportToolsModel $importToolsModel */
            $importToolsModel = $form->getData();
            $importToolsModel->endDate = new \DateTime($importToolsModel->endDate->format('Y-m-d 23:59'));
            $importToolsModel->path = $importToolsModel->anlage->getPathToImportScript();
            $importToolsModel->importType = (string)$form->get('importType')->getData();
            // Start recalculation
            if ($form->get('importType')->getData() == null) {
                $output .= 'Please select what you like to import.<br>';
                $start = false;
            }

            if ($importToolsModel->anlage->getHasPPC() != 1 && $importToolsModel->importType == 'api-import-ppc'){
                $output .= 'This plant has not PPC!<br>';
                $start = false;

            }
            if ($start){
                if ($form->get('function')->getData() != null) {
                    switch ($form->get('function')->getData()) {
                        case 'api-import-data':
                            $output = '<h3>Import API Data:</h3>';
                            $job = 'Import API Data('.$importToolsModel->importType.') – from ' . $importToolsModel->startDate->format('Y-m-d H:i') . ' until ' . $importToolsModel->endDate->format('Y-m-d H:i');
                            $job .= " - " . $this->getUser()->getname();
                            $userId = $this->getUser()->getUserId();

                            $logId = $logMessages->writeNewEntry($importToolsModel->anlage, 'Import API Data', $job, $userId);
                            $message = new ImportData($importToolsModel->anlage->getAnlId(), $importToolsModel->startDate, $importToolsModel->endDate, $importToolsModel->path, $importToolsModel->importType, $logId);
                            $messageBus->dispatch($message);
                            $output .= 'Command was send to messenger! Will be processed in background.<br>';
                            break;
                        default:
                            $output .= 'something went wrong!<br>';
                    }

                } else {
                    $output .= 'Please select a function.<br>';
                }
            }
        }

        return $this->render('import_tools/index.html.twig', [
            'importToolsForm' => $form,
            'output' => $output,
        ]);
    }

    /**
     * VCOM Import by Cronjob; PLants direct by symfony (configured in backend)
     *
     * @param AnlagenRepository $anlagenRepo
     * @param ImportService $importService
     * @return Response
     * @throws NonUniqueResultException
     * @throws JsonException
     */
    #[Route('/import/cron', name: 'import_cron')]
    public function importCron(AnlagenRepository $anlagenRepo, ImportService $importService): Response
    {

        //get all Plants for Import via via Cron
        $anlagen = $anlagenRepo->findAllSymfonyImport();

        $time = time();
        $time -= $time % 900;
        $start = $time - (4 * 3600);
        $end = $time;

        foreach ($anlagen as $anlage) {
            $importService->prepareForImport($anlage, $start, $end);
        }

        return new Response('This is used for import via cron job.', Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    /**
     * Manuel Import PLants direct by symfony via URL (configured in backend)
     *
     * @param int $id
     * @param string $from
     * @param string $to
     * @param AnlagenRepository $anlagenRepo
     * @param ImportService $importService
     * @return Response
     * @throws NonUniqueResultException
     * @throws JsonException
     */
    #[Route('/import/manuel', name: 'import_manuell')]
    public function importManuell(#[MapQueryParameter] int $id, #[MapQueryParameter] string $from, #[MapQueryParameter] string $to, AnlagenRepository $anlagenRepo, ImportService $importService): Response
    {
        $fromts = strtotime("$from 00:00:00");
        $tots = strtotime("$to 23:59:00");

        //get all Plants for Import via via Cron
        $anlage = $anlagenRepo->findOneByIdAndJoin($id);

        for ($dayStamp = $fromts; $dayStamp <= $tots; $dayStamp += 24*3600) {

            $from_new = strtotime(date('Y-m-d 00:15', $dayStamp));
            $to_new = strtotime(date('Y-m-d 23:59', $dayStamp));
            $currentDay = date('d', $dayStamp);

            // Proof if date = today, if yes set $to to current DateTime
            if (date('Y', $to_new) == date('Y') && date('m', $to_new) == date('m') && $currentDay == date('d')) {
                $hour = date('H');
                $minute = date('i');
                $to_new = strtotime(date("Y-m-d $hour:$minute"), $to_new);
            }

            $minute = (int)date('i');
            while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {
                sleep(20);
                $minute = (int)date('i');
            }
            $importService->prepareForImport($anlage, $from_new, $to_new);

            sleep(1);
        }


        return new Response('This is used for import via manual Import.', Response::HTTP_OK, ['Content-Type' => 'text/html']);
    }

    /**
     * Import der eGrid Daten aus Excel.
     * Format Kopf Zeile muss 'stamp' und '' haben.
     * Format 'stamp' = 'Y-m-d H:i'
     *
     * @param Request $request
     * @param UploaderHelper $uploaderHelper
     * @param AnlagenRepository $anlagenRepository
     * @param PdoService $pdoService
     * @param $uploadsPath
     * @return Response
     */
    #[Route(path: '/import/egrid', name: 'import_egrid')]
    public function importEGrid(Request $request, UploaderHelper $uploaderHelper, AnlagenRepository $anlagenRepository, PdoService $pdoService, $uploadsPath): Response
    {

        $form = $this->createForm(ImportEGridFormType::class);
        $form->handleRequest($request);

        $output = '';
        $indexEzevu = $indexStamp = 0;

        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked() && $request->getMethod() == 'POST') {
            $anlageForm = $form['anlage']->getData();
            $anlage = $anlagenRepository->findOneBy(['anlId' => $anlageForm]);
            $anlageId = $anlage->getAnlagenId();
            $dataBaseNTable = $anlage->getDbNameIst();
            $uploadedFile = $form['file']->getData();
            if ($uploadedFile) {
                if ($xlsx = simpleXLSX::parse($uploadedFile->getPathname())) {
                    $conn = $pdoService->getPdoPlant();
                    foreach ($xlsx->rows(0) as $key => $row) {
                        if ($key === 0) {
                            $data_fields = $row;
                            $indexStamp = array_search('stamp', $data_fields);
                            if ($indexStamp === false) $indexStamp  = array_search('Stamp', $data_fields);
                            $indexEzevu = array_search('e_z_evu', $data_fields);
                            if ($indexEzevu === false) $indexEzevu  = array_search('eGridValue', $data_fields);
                        } else {
                            $eZEvu = $row[$indexEzevu] != '' ? $row[$indexEzevu] : NULL;
                            $stamp = $row[$indexStamp];
                            $stmt= $conn->prepare("UPDATE $dataBaseNTable SET e_z_evu = ? WHERE stamp = ?");
                            $stmt->execute([$eZEvu, $stamp]);
                        }
                    }

                } else {
                    $output .= "No valid XLSX File.<br>";
                    $output .= "(" . SimpleXLSX::parseError() . ")";
                }
            }
        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('import/eGridImport.html.twig', [
            'form'     => $form,
            'output'   => $output,
        ]);
    }

    /**
     * Import der PvSyst Stunden Daten aus CSV (Excel).
     *
     * @param Request $request
     * @param PvSystImportService $pvSystImport
     * @return Response
     */
    #[Route(path: '/import/pvsyst', name: 'import_pvsyst')]
    public function importPvSyst(Request $request, PvSystImportService $pvSystImport): Response
    {
        $filename = null;
        $form = $this->createForm(ImportPvSystFormType::class);
        $form->handleRequest($request);

        $output = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('preview')->isClicked()) {

            $anlage = $form->getData()->anlage;

            /** @var UploadedFile $uploadedFile */
            /** @var UploadedFile $file */
            $uploadedFile = $form['file']->getData();
            $destination = $this->getParameter('kernel.project_dir') . '/tempfiles';
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
            $file = $uploadedFile->move($destination, $newFilename);
            $filename = $file->getPathname();
            $fileStream = fopen($file->getPathname(), 'r');
            for ($n = 1; $n <= 20; $n++) {
                $output .= fgets($fileStream) . '<br>';
            }
        }

        if ($form->isSubmitted() && $form->isValid() && $form->get('import')->isClicked()) {
            $anlage = $form->getData()->anlage;
            $file = $form['filename']->getData();
            $fileStream = fopen($file, 'r');
            $output = $pvSystImport->import($anlage, $fileStream, $form['separator']->getData(), $form['dateFormat']->getData());

            unlink($file);
        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('import/pvSystImport.html.twig', [
            'form'     => $form,
            'filename' => $filename,
            'output'   => $output,
        ]);
    }

    /**
     * Import der FB Excel Liste zur Erstellung von Tickets
     *
     * @param Request $request
     * @param ImportTicketFBExcel $fbExcelImport
     * @return Response
     */
    #[Route(path: '/import/fbexcel', name: 'import_fb_excel')]
    public function importFbExcel(Request $request, ImportTicketFBExcel $fbExcelImport): Response
    {
        $filename = null;
        $form = $this->createForm(ImportPvSystFormType::class);
        $form->handleRequest($request);

        $output = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('preview')->isClicked()) {

            $anlage = $form->getData()->anlage;

            /** @var UploadedFile $uploadedFile */
            /** @var UploadedFile $file */
            $uploadedFile = $form['file']->getData();
            $destination = $this->getParameter('kernel.project_dir') . '/tempfiles';
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
            $file = $uploadedFile->move($destination, $newFilename);
            $filename = $file->getPathname();
            $fileStream = fopen($file->getPathname(), 'r');
            for ($n = 1; $n <= 20; $n++) {
                $output .= fgets($fileStream) . '<br>';
            }
        }

        if ($form->isSubmitted() && $form->isValid() && $form->get('import')->isClicked()) {
            $anlage = $form->getData()->anlage;
            $file = $form['filename']->getData();
            $fileStream = fopen($file, 'r');

            $output = "Jetzt sollte die Import ROUTINE STARTEN<br>";
            $output .= $fbExcelImport->import($anlage, $fileStream, $form['separator']->getData() , 'd.m.y H:i'); //, $form['separator']->getData(), $form['dateFormat']->getData());

            unlink($file);
        }

        // Wenn Close geklickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('import/ticketImport.html.twig', [
            'form'     => $form,
            'filename' => $filename,
            'output'   => $output,
        ]);
    }

}