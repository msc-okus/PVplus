<?php

namespace App\Controller;

use App\Form\Import\ImportEGridFormType;
use App\Form\Import\ImportPvSystFormType;
use App\Form\Model\ImportPvSystModel;
use App\Repository\AnlagenRepository;
use App\Service\Import\ImportTicketFBExcel;
use App\Service\Import\PvSystImportService;
use App\Service\PdoService;
use App\Service\UploaderHelper;
use Gedmo\Sluggable\Util\Urlizer;
use Shuchkin\SimpleXLSX;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportMenuController extends AbstractController
{
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
        $prefills = new ImportPvSystModel();
        $prefills->separator = ';';
        $prefills->dateFormat = 'd/m/y h:m';
        $prefills->filename = null;

        $form = $this->createForm(ImportPvSystFormType::class, $prefills );
        $form->handleRequest($request);


        $output = '';

        if ($form->isSubmitted() && $form->isValid() && $form->get('preview')->isClicked()) {
            /** @var UploadedFile $uploadedFile */
            /** @var UploadedFile $file */
            $uploadedFile = $form['file']->getData();
            $destination = $this->getParameter('kernel.project_dir') . '/tempfiles';
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
            $file = $uploadedFile->move($destination, $newFilename);
            $prefills->filename = $file->getPathname();
            $fileStream = fopen($file->getPathname(), 'r');
            for ($n = 1; $n <= 20; $n++) {
                $line = fgets($fileStream);
                //try to analyse wich field separator is used (default is ';')
                if ($n === 11) {
                    if (str_contains($line, ",")){
                        $prefills->separator = ',';
                    }
                }
                $output .= $line . '<br>';
            }
            $form = $this->createForm(ImportPvSystFormType::class, $prefills );
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
            'prefills' => $prefills,
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
