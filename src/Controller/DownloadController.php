<?php

namespace App\Controller;
use App\Service\GetPdoService;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Form\DownloadAnalyse\DownloadAnalyseFormExportType;
use App\Form\DownloadAnalyse\DownloadAnalyseFormType;
use App\Form\DownloadData\DownloadDataFormType;
use App\Form\Model\DownloadAnalyseModel;
use App\Form\Model\DownloadDataModel;
use App\Reports\Download\DownloadReport;
use App\Service\DownloadAnalyseService;
use App\Service\DownloadDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DownloadController extends AbstractController
{
    #[Route(path: '/download', name: 'app_download')]
    public function dataDownload(Request $request, DownloadDataService $downloadData)
    {
        $form = $this->createForm(DownloadDataFormType::class);
        $form->handleRequest($request);
        $output = '';
        // Wenn Calc gelickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked()) {
            /* @var DownloadDataModel $downloadModel */
            $downloadModel = $form->getData();
            $start = $downloadModel->startDate->format('Y-m-d 00:00');
            $end = $downloadModel->endDate->format('Y-m-d 23:59');
            // Print Headline
            switch ($downloadModel->data) {
                case 'all':
                    $output = '<h3>All Data: '.$downloadModel->anlage->getAnlName().'</h3>';
                    $output .= $downloadData->getAllSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case 'ac':
                    $output = '<h3>AC Data: '.$downloadModel->anlage->getAnlName().'</h3>';
                    $output .= $downloadData->getAcSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case 'dc':
                    $output = '<h3>DC Data: '.$downloadModel->anlage->getAnlName().'</h3>';
                    $output .= $downloadData->getDcSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case 'avail':
                    $output = '<h3>Availbility Data: '.$downloadModel->anlage->getAnlName().'</h3>';
                    $output .= $downloadData->getAvailabilitySingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case 'irr':
                    $output = '<h3>Irradiation Data: '.$downloadModel->anlage->getAnlName().'</h3>';
                    $output .= $downloadData->getIrrSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
            }
        }
        // Wenn Close gelickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('downloadData/index.html.twig', [
            'downloadForm' => $form->createView(),
            'output' => $output,
            'section' => 'data',
        ]);
    }

    #[Route(path: '/download/analyse/{formview}/{plantIdexp}', name: 'app_analyse_download', defaults: ['formview' => '-', 'plantIdexp' => 0])]
    public function downloadAnalyse($formview, $plantIdexp, Request $request, DownloadAnalyseService $analyseService)
    {
        // das Formular für die Datumsselektion
        $form = $this->createForm(DownloadAnalyseFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->get('calc')->isClicked()) { // 'calc' == generate Analyse
            /* @var DownloadAnalyseModel $downloadAnalyseModel */
            $downloadAnalyseModel = $form->getData();
            /* @var Anlage $anlage */
            $anlage = $downloadAnalyseModel->anlage;
            $plantId = $anlage->getAnlId();
            $plantName = $anlage->getAnlName();
        }
        if ($plantIdexp > 0) {
            $plantId = $plantIdexp;
        }
        // das hidden Formular für den Download
        $formPdfDownload = $this->createForm(DownloadAnalyseFormExportType::class, null, ['anlagenid' => $plantId]);
        $formPdfDownload->handleRequest($request);
        $output = '';
        $anlage = 0;
        $outputchart = [];
        $outputtable = [];
        // Wenn Calc (generate Analyse) gelickt wird mache dies:
        if (($form->isSubmitted() && $form->get('calc')->isClicked()) || ($formPdfDownload->isSubmitted() && $formPdfDownload->get('export')->isClicked())) {
            /* @var DownloadAnalyseModel $downloadAnalyseModel */
            if ($formview != 'download') {
                $downloadAnalyseModel = $form->getData();
            } else {
                $downloadAnalyseModel = $formPdfDownload->getData();
            }

            if ($formview != 'download') {
                $doctype = 'default';
                $anlage = $downloadAnalyseModel->anlage;
                $plantId = $anlage->getAnlId();
                $year = $downloadAnalyseModel->years;
                $month = $downloadAnalyseModel->months;
                $day = $downloadAnalyseModel->days;
                $formPdfDownload->get('year')->setData($year);
                $formPdfDownload->get('month')->setData($month);
                $formPdfDownload->get('day')->setData($day);
                $useGridMeterDayData = $anlage->getUseGridMeterDayData();
                $showAvailability = $anlage->getShowAvailability();
                $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
                $formatBody = '92px 0px 0px 0px;';
            } else {
                $anlage = $downloadAnalyseModel['anlageexport'];
                $year = $downloadAnalyseModel['year'];
                $month = $downloadAnalyseModel['month'];
                $day = $downloadAnalyseModel['day'];
                $doctype = $downloadAnalyseModel['documenttype'];
                $plantName = $anlage->getAnlName();
                $plantPower = $anlage->getPower();
                $useGridMeterDayData = $anlage->getUseGridMeterDayData();
                $showAvailability = $anlage->getShowAvailability();
                $showAvailabilitySecond = $anlage->getShowAvailabilitySecond();
                $formatBody = '65px 30px 35px 30px;';
            }

            // Wenn nur das Jahr ausgewaehlt wurde
            if ($month == '' && $day == '') {
                $start = $year.'-01-01 00:00';
                $end = $year.'-12-31 23:59';
                $tableType = 'default';
                $landscape = false;

                for ($i = 1; $i <= 12; ++$i) { // $i == Monat
                    if ($i < 10) {
                        $month_transfer = "0$i";
                    } else {
                        $month_transfer = $i;
                    }

                    /** @var AnlagenPR $output */
                    $output = $analyseService->getAllSingleSystemData($anlage, $year, $i, 1);
                    $dcData = $analyseService->getDcSingleSystemData($anlage, $start, $end, '%m');
                    $dcDataExpected = $analyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%m');

                    if ($output) {
                        $outputtable[] = [
                            'time' => $output->getstamp()->format('M'),
                            'irradiation' => (float) $output->getIrrMonth(),
                            'powerEGridExtMonth' => (float) $output->getpowerEGridExt(),
                            'powerEvuMonth' => (float) $output->getPowerEvuMonth(),
                            'powerActMonth' => (float) $output->getpowerActMonth(),
                            'powerDctMonth' => (float) $dcData[$i]['actdc'],
                            'powerExpMonth' => (float) $output->getpowerExpMonth(),
                            'powerExpDctMonth' => (float) $dcDataExpected[$i]['expdc'],
                            'prEGridExtMonth' => (float) $output->getprEGridExtMonth(),
                            'prEvuMonth' => (float) $output->getprEvuMonth(),
                            'prActMonth' => (float) $output->getprActMonth(),
                            'prExpMonth' => (float) $output->getprExpMonth(),
                            'plantAvailability' => (float) $output->getplantAvailability(),
                            'plantAvailabilitySecond' => (float) $output->getplantAvailabilitySecond(),
                            'panneltemp' => (float) $output->getpanneltemp(),
                        ];
                    }
                }
                $outputchart = [];

                $headLine = 'Yearly Report';
            }

            // Wenn Jahr und Monat ausgewählt wurden
            if ($month >= 1 && $day == '') {
                $start = $year.'-'.$month.'-01 00:00';
                $end = $year.'-'.$month.'-31 23:59';
                $tableType = 'default';
                $landscape = false;

                $output = $analyseService->getAllSingleSystemData($anlage, $year, $month, 2);
                $dcData = $analyseService->getDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');
                $dcDataExpected = $analyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');

                if ($output) {
                    for ($i = 0; $i < count($output); ++$i) {
                        $outputtable[] =
                            [
                                'time' => $output[$i]->getstamp()->format('M-d'),
                                'irradiation' => (float) $output[$i]->getirradiation(),
                                'powerEGridExtMonth' => (float) $output[$i]->getpowerEGridExt(),
                                'powerEvuMonth' => (float) $output[$i]->getPowerEvu(),
                                'powerActMonth' => (float) $output[$i]->getpowerAct(),
                                'powerDctMonth' => (float) $dcData[$i]['actdc'],
                                'powerExpMonth' => (float) $output[$i]->getpowerExp(),
                                'powerExpDctMonth' => (float) $dcDataExpected[$i]['expdc'],
                                'prEGridExtMonth' => (float) $output[$i]->getprEGridExtMonth(),
                                'prEvuMonth' => (float) $output[$i]->getprEvuMonth(),
                                'prActMonth' => (float) $output[$i]->getprActMonth(),
                                'prExpMonth' => (float) $output[$i]->getprExpMonth(),
                                'plantAvailability' => (float) $output[$i]->getplantAvailability(),
                                'plantAvailabilitySecond' => (float) $output[$i]->getplantAvailabilitySecond(),
                                'panneltemp' => (float) $output[$i]->getpanneltemp(),
                            ];
                    }
                }
                $headLine = 'Monthly Report';
            }

            // Wenn Jahr, Monat und Tag ausgewaehlt wurden
            if ($month >= 1 && $day >= 1) {
                $start = $year.'-'.$month.'-'.$day.' 00:00';
                $end = $year.'-'.$month.'-'.$day.' 23:59';
                $tableType = 'daybase';

                $outputchart = [];
                $outputtable = $analyseService->getAllSingleSystemDataForDay($anlage, $start, $end, '%H:00', 'Date Time');

                $headLine = 'Dayly Report';
            }
        }

        // Wenn Close gelickt wird mache dies:
        if ($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        if ($form->isSubmitted()) {
            $report = $outputtable;


        // Send the file to the browser.
        // readfile($filename);
        } else {
            $report = '';
        }



        return $this->render('downloadData/download.html.twig', [
            'downloadAnalysesExportForm' => $formPdfDownload->createView(),
            'downloadAnalysesForm' => $form->createView(),
            'tableType' => $tableType,
            'showAvailability' => $showAvailability,
            'showAvailabilitySecond' => $showAvailabilitySecond,
            'useGridMeterDayData' => $useGridMeterDayData,
            'report' => $report,
            'section' => 'analyse',
        ]);
    }
}
