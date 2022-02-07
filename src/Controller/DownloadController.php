<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Form\DownloadAnalyse\DownloadAnalyseFormExportType;
use App\Form\DownloadData\DownloadDataFormType;
use App\Form\DownloadAnalyse\DownloadAnalyseFormType;
use App\Form\Model\DownloadDataModel;
use App\Form\Model\DownloadAnalyseModel;
use App\Service\DownloadDataService;
use App\Service\DownloadAnalyseService;
use koolreport\KoolReport;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\PVSystDatenRepository;
use App\Service\PVSystService;
use App\Service\ReportEpcService;
use App\Service\ReportService;
use Doctrine\ORM\EntityManagerInterface;
use \App\Reports\Download\DownloadReport;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Hisune\EchartsPHP\ECharts;
use \Hisune\EchartsPHP\Doc\IDE\Series;
use \Hisune\EchartsPHP\Config;

use Nuzkito\ChromePdf\ChromePdf;

class DownloadController extends AbstractController
{
    /**
     * @Route("/download", name="app_download")
     */
    public function dataDownload(Request $request, DownloadDataService $downloadData)
    {
        $form = $this->createForm(DownloadDataFormType::class);
        $form->handleRequest($request);
        $output = '';

        // Wenn Calc gelickt wird mache dies:
        if($form->isSubmitted() && $form->isValid() && $form->get('calc')->isClicked()) {

            /* @var DownloadDataModel $downloadModel */
            $downloadModel = $form->getData();
            $start = $downloadModel->startDate->format('Y-m-d 00:00');
            $end = $downloadModel->endDate->format('Y-m-d 23:59');
            // Print Headline
            switch ($downloadModel->data) {
                case ('all'):
                    $output = "<h3>All Data: ".$downloadModel->anlage->getAnlName()."</h3>";
                    $output .= $downloadData->getAllSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case ('ac'):
                    $output = "<h3>AC Data: ".$downloadModel->anlage->getAnlName()."</h3>";
                    $output .= $downloadData->getAcSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case ('dc'):
                    $output = "<h3>DC Data: ".$downloadModel->anlage->getAnlName()."</h3>";
                    $output .= $downloadData->getDcSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case ('avail'):
                    $output = "<h3>Availbility Data: ".$downloadModel->anlage->getAnlName()."</h3>";
                    $output .= $downloadData->getAvailabilitySingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
                case ('irr'):
                    $output = "<h3>Irradiation Data: ".$downloadModel->anlage->getAnlName()."</h3>";
                    $output .= $downloadData->getIrrSingleSystemData($downloadModel->anlage, $start, $end, $downloadModel->intervall, 'Date Time');
                    break;
            }

        }

        // Wenn Close gelickt wird mache dies:
        if($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('downloadData/index.html.twig', [
            'downloadForm' => $form->createView(),
            'output'    => $output,
            'section'   => 'data',
        ]);
    }

    /**
     * @Route("/download/analyse/{formview}/{plantIdexp}", name="app_analyse_download", defaults={"formview"="-", "plantIdexp"=0})
     */
    public function downloadAnalyse($formview, $plantIdexp, Request $request, DownloadAnalyseService $analyseService){

        //das Formular für die Datumsselektion
        $form = $this->createForm(DownloadAnalyseFormType::class);
        $form->handleRequest($request);
        $plantId = 91;

        if($form->isSubmitted() && $form->get('calc')->isClicked()) {
            /* @var DownloadAnalyseModel $downloadAnalyseModel */
            $downloadAnalyseModel = $form->getData();
            /** @var Anlage $anlage */
            $anlage = $downloadAnalyseModel->anlage;
            $plantId = $anlage->getAnlId();
            $plantName = $anlage->getAnlName();
        }
        if($plantIdexp > 0){
            $plantId = $plantIdexp;

        }

        #das hidden Formular für den Download
        $formPdfDownload = $this->createForm(DownloadAnalyseFormExportType::class,null, array("anlagenid" => $plantId));
        $formPdfDownload->handleRequest($request);

        $output = '';
        $anlage = 0;
        $outputchart = [];
        $outputtable = [];
        $my_var = function(){
            ?>
            Date Time<br>(per hour)
            <?php
        };

        // Wenn Calc gelickt wird mache dies:
        if(($form->isSubmitted() && $form->get('calc')->isClicked()) || ($formPdfDownload->isSubmitted() && $formPdfDownload->get('export')->isClicked())) {

            /* @var DownloadAnalyseModel $downloadAnalyseModel */
            if($formview != 'download') {
                $downloadAnalyseModel = $form->getData();
            }else{
                $downloadAnalyseModel = $formPdfDownload->getData();
            }

            if($formview != 'download'){
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
                $formatBody = "92px 0px 0px 0px;";
            }else{
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
                $formatBody = "65px 30px 35px 30px;";
            }

            //Wenn nur das Jahr ausgewaehlt wurde
            if ($month == '' && $day == ''){
                $start = $year.'-01-01 00:00';
                $end = $year.'-12-31 23:59';
                $tableType = "default";
                $landscape = false;

                for ($i = 1; $i < 13; $i++) {
                    if($i < 10){
                        $month_transfer = "0$i";
                    }else{
                        $month_transfer = $i;
                    }

                    /** @var AnlagenPR $output */
                    $output = $analyseService->getAllSingleSystemData($anlage, $year, "$month_transfer", 1);

                    $dcData = $analyseService->getDcSingleSystemData($anlage, $start, $end, '%m');
                    $dcDataExpected = $analyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%m');

                    if($output){

                        $outputtable[] =
                            [
                                "time" => $output->getstamp()->format('M'),
                                "irradiation" => (float)$output->getIrrMonth(),
                                "powerEGridExtMonth" => (float)$output->getpowerEGridExt(),
                                "PowerEvuMonth" => (float)$output->getPowerEvuMonth(),
                                "powerActMonth" => (float)$output->getpowerActMonth(),
                                "powerDctMonth" => (float)$dcData[$i]['actdc'],
                                "powerExpMonth" => (float)$output->getpowerExpMonth(),
                                "powerExpDctMonth" => (float)$dcDataExpected[$i]['expdc'],
                                "prEGridExtMonth" => (float)$output->getprEGridExtMonth(),
                                "prEvuMonth" => (float)$output->getprEvuMonth(),
                                "prActMonth" => (float)$output->getprActMonth(),
                                "prExpMonth" => (float)$output->getprExpMonth(),
                                "plantAvailability" => (float)$output->getplantAvailability(),
                                "plantAvailabilitySecond" => (float)$output->getplantAvailabilitySecond(),
                                "panneltemp" => (float)$output->getpanneltemp(),
                            ];
                    }
                }

                $outputchart = [];

                $headLine = 'Yearly Report';
            }
            //Wenn Jahr und Monat ausgewaehlt wurden
            if ($month >= 1 && $day == ''){
                $start = $year.'-'.$month.'-01 00:00';
                $end = $year.'-'.$month.'-31 23:59';
                $tableType = "default";
                $landscape = false;

                $output = $analyseService->getAllSingleSystemData($anlage, $year, "$month", 2);
                $dcData = $analyseService->getDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');
                $dcDataExpected = $analyseService->getEcpectedDcSingleSystemData($anlage, $start, $end, '%d.%m.%Y');

                if($output){
                    for ($i = 0; $i < count($output); $i++) {
                        $outputtable[] =
                            [
                                "time" => $output[$i]->getstamp()->format('M-d'),
                                "irradiation" => (float)$output[$i]->getirradiation(),
                                "powerEGridExtMonth" => (float)$output[$i]->getpowerEGridExt(),
                                "PowerEvuMonth" => (float)$output[$i]->getPowerEvu(),
                                "powerActMonth" => (float)$output[$i]->getpowerAct(),
                                "powerDctMonth" => (float)$dcData[$i]['actdc'],
                                "powerExpMonth" => (float)$output[$i]->getpowerExp(),
                                "powerExpDctMonth" => (float)$dcDataExpected[$i]['expdc'],
                                "prEGridExtMonth" => (float)$output[$i]->getprEGridExtMonth(),
                                "prEvuMonth" => (float)$output[$i]->getprEvuMonth(),
                                "prActMonth" => (float)$output[$i]->getprActMonth(),
                                "prExpMonth" => (float)$output[$i]->getprExpMonth(),
                                "plantAvailability" => (float)$output[$i]->getplantAvailability(),
                                "plantAvailabilitySecond" => (float)$output[$i]->getplantAvailabilitySecond(),
                                "panneltemp" => (float)$output[$i]->getpanneltemp(),
                            ];
                    }
                }
                $headLine = 'Monthly Report';
            }

            //Wenn Jahr, Monat und Tag ausgewaehlt wurden
            if ($month >= 1 && $day >= 1){
                $start = $year.'-'.$month.'-'.$day.' 00:00';
                $end = $year.'-'.$month.'-'.$day.' 23:59';
                $tableType = "daybase";
                $landscape = false;

                $outputchart = [];
                $outputtable = $analyseService->getAllSingleSystemDataForDay($anlage, $start, $end, '%H:00', 'Date Time');

                $headLine = 'Dayly Report';
            }


        }

        // Wenn Close gelickt wird mache dies:
        if($form->isSubmitted() && $form->isValid() && $form->get('close')->isClicked()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $params[] = [
            'tableType' => $tableType,
            'downloadHeadline' => $headLine,
            'downloadPlantName' => $plantName,
            'doctype'            => $doctype,
            'showAvailability' => $showAvailability,
            'showAvailabilitySecond' => $showAvailabilitySecond,
            'useGridMeterDayData' => $useGridMeterDayData,
            'formatBody' => $formatBody,
            'plant_power' => $plantPower,
            'footerType' => 'download',
        ];

        $downloadTable =  new DownloadReport(
            [
                "download" => $outputtable,
                "params" => $params,
            ]
        );

        if($formview == 'download'){
            $currentDate = date('Y-m-d H-i');
            $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';

            switch ($doctype) {
                case 'excel':
                    $excelFilename = 'Download ' . $anlage->getAnlName() . ' - ' . $currentDate . ".xlsx";
                    $output = $downloadTable->run()->render(true);
                    $downloadTable->run();
                    $downloadTable->exportToXLSX('DownloadReport')->toBrowser($excelFilename);
                    exit; // Ohne exit führt es unter manchen Systemen (Browser) zu fehlerhaften Downloads
                    break;
                default:
                    $output = $downloadTable->run()->render('DownloadReport', true);
                    $pdfFilename = 'Download ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
                    $settings = [
                        // 'useLocalTempFolder' => true,
                        "pageWaiting" => "networkidle2", //load, domcontentloaded, networkidle0, networkidle2
                    ];

                    $downloadTable->run();
                    $pdfOptions = [
                        'format'                => 'A4',
                        'landscape'             => $landscape,
                        'noRepeatTableFooter'   => false,
                        'printBackground'       => true,
                        'displayHeaderFooter'   => true,
                    ];

                    $downloadTable->cloudExport('DownloadReport')
                        ->chromeHeadlessio($secretToken)
                        ->settings($settings)
                        ->pdf($pdfOptions)
                        ->toBrowser($pdfFilename);
                    exit;
            }

        }

        if($form->isSubmitted()){
            $report =  $downloadTable->run()->render(true);

// specify the route to the binary.
            $pdf = new ChromePdf('/usr/bin/chromium');

// Route when PDF will be saved.
            $pdf->output('/usr/home/pvpluy/public_html/result.pdf');

            $pdf->generateFromHtml($report);

            $filename = "/usr/home/pvpluy/public_html/result.pdf";
            $pdf->output($filename);
// Header content type
            header("Content-type: application/pdf");

            header("Content-Length: " . filesize($filename));

// Send the file to the browser.
            #readfile($filename);

        }else{
            $report = '';
        }



        return $this->render('downloadData/download.html.twig', [
            'downloadAnalysesExportForm' => $formPdfDownload->createView(),
            'downloadAnalysesForm' => $form->createView(),
            'report'    => $report,
            'download' => '',
            'section'  => 'analyse',
        ]);
    }

}
