<?php

namespace App\Controller;

use App\Entity\AnlagenReports;
use App\Form\AssetManagement\AssetManagementeReportFormType;
use App\Form\Ticket\TicketFormType;
use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\ReportsRepository;
use App\Service\AssetManagementService;
use Doctrine\ORM\EntityManagerInterface;
use Nuzkito\ChromePdf\ChromePdf;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;

class AssetManagementController extends BaseController
{
    use G4NTrait;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/asset/report/{id}/{month}/{year}/{export}/{pages}", name="report_asset_management", defaults={"export" = 0, "pages" = 0})
     */
    public function assetReport($id, $month, $year, $export, $pages, AssetManagementService $assetManagement, AnlagenRepository $anlagenRepository, Request $request, EntityManagerInterface $em, ReportsRepository $reportRepo)
    {
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $report = new AnlagenReports();
        if($reportRepo->findOneByAMY($anlage,$month,$year)[0]) {
            $report = $reportRepo->findOneByAMY($anlage, $month, $year)[0];
            $output = $report->getContentArray();
        }
        else {
            $output = $assetManagement->assetReport($anlage, $month, $year, $pages);
            //submitting the report

        }
        //dd($output,count($output["plantAvailabilityMonth"]));
        $form = $this->createForm(AssetManagementeReportFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $output["data"] = $data;

            //if(($data['ProductionPos'] != $data['AvailabilityPos']) && ($data['AvailabilityPos'] != $data['EconomicsPos']) && ($data['ProductionPos'] != $data['EconomicsPos']))
            $result = $this->render('report/assetreport.html.twig', [
                'invNr' => count($output["operations_currents_dayly_table"]),
                'comments' =>$report->getComments(),
                'data' => $data,
                'anlage' => $anlage,
                'year' => $output['year'],
                'month' => $output['month'],
                'reportmonth' => $output['reportmonth'],
                'montharray' => $output['monthArray'],
                'degradation' => $output['degradation'],
                'forecast_PVSYST_table' => $output['forecast_PVSYST_table'],
                'forecast_PVSYST' => $output['forecast_PVSYST'],
                'forecast_G4N_table' => $output['forecast_G4N_table'],
                'forecast_G4N' => $output['forecast_G4N'],
                'dataMonthArray' => $output['dataMonthArray'],
                'dataMonthArrayFullYear' => $output['dataMonthArrayFullYear'],
                'dataCfArray' => $output['dataCfArray'],
                'operations_right' => $output['operations_right'],
                'table_overview_monthly' => $output['table_overview_monthly'],
                'losses_t1' => $output['losses_t1'],
                'losses_t2' => $output['losses_t2'],
                'losses_year' => $output['losses_year'],
                'losses_monthly' => $output['losses_monthly'],
                'production_monthly_chart' => $output['production_monthly_chart'],
                'operations_monthly_right_pvsyst_tr1' => $output['operations_monthly_right_pvsyst_tr1'],
                'operations_monthly_right_pvsyst_tr2' => $output['operations_monthly_right_pvsyst_tr2'],
                'operations_monthly_right_pvsyst_tr3' => $output['operations_monthly_right_pvsyst_tr3'],
                'operations_monthly_right_pvsyst_tr4' => $output['operations_monthly_right_pvsyst_tr4'],
                'operations_monthly_right_pvsyst_tr5' => $output['operations_monthly_right_pvsyst_tr5'],
                'operations_monthly_right_pvsyst_tr6' => $output['operations_monthly_right_pvsyst_tr6'],
                'operations_monthly_right_pvsyst_tr7' => $output['operations_monthly_right_pvsyst_tr7'],
                'operations_monthly_right_g4n_tr1' => $output['operations_monthly_right_g4n_tr1'],
                'operations_monthly_right_g4n_tr2' => $output['operations_monthly_right_g4n_tr2'],
                'operations_monthly_right_g4n_tr3' => $output['operations_monthly_right_g4n_tr3'],
                'operations_monthly_right_g4n_tr4' => $output['operations_monthly_right_g4n_tr4'],
                'operations_monthly_right_g4n_tr5' => $output['operations_monthly_right_g4n_tr5'],
                'operations_monthly_right_g4n_tr6' => $output['operations_monthly_right_g4n_tr6'],
                'operations_monthly_right_g4n_tr7' => $output['operations_monthly_right_g4n_tr7'],
                'operations_monthly_right_iout_tr1' => $output['operations_monthly_right_iout_tr1'],
                'operations_monthly_right_iout_tr2' => $output['operations_monthly_right_iout_tr2'],
                'operations_monthly_right_iout_tr3' => $output['operations_monthly_right_iout_tr3'],
                'operations_monthly_right_iout_tr4' => $output['operations_monthly_right_iout_tr4'],
                'operations_monthly_right_iout_tr5' => $output['operations_monthly_right_iout_tr5'],
                'operations_monthly_right_iout_tr6' => $output['operations_monthly_right_iout_tr6'],
                'operations_monthly_right_iout_tr7' => $output['operations_monthly_right_iout_tr7'],
                'table_overview_dayly' => $output['table_overview_dayly'],
                'plantAvailabilityCurrentYear' => $output['plantAvailabilityCurrentYear'],
                'daysInReportMonth' => $output['daysInReportMonth'],
                'tableColsLimit' => $output['tableColsLimit'],
                'acGroups' => $output['acGroups'],
                'availability_Year_To_Date' => $output['availability_Year_To_Date'],
                'failures_Year_To_Date' => $output['failures_Year_To_Date'],
                'plant_availability' => $output['plant_availability'],
                'actual' => $output['actual'],
                'plantAvailabilityMonth' => $output['plantAvailabilityMonth'],
                'operations_currents_dayly_table' => $output['operations_currents_dayly_table'],
                'income_per_month' => $output['income_per_month'],
                'income_per_month_chart' => $output['income_per_month_chart'],
                'economicsMandy' => $output['economicsMandy'],
                'total_Costs_Per_Date' => $output['total_Costs_Per_Date'],
                'operating_statement_chart' => $output['operating_statement_chart'],
                'economicsCumulatedForecast' => $output['economicsCumulatedForecast'],
                'economicsCumulatedForecastChart' => $output['economicsCumulatedForecastChart'],
                'lossesComparedTable' => $output['lossesComparedTable'],
                'losses_compared_chart' => $output['losses_compared_chart'],
                'lossesComparedTableCumulated' => $output['lossesComparedTableCumulated'],
                'cumulated_losses_compared_chart' => $output['cumulated_losses_compared_chart'],
            ]);


            if ($export == 1) {
                $report = new AnlagenReports();

                $report->setAnlage($anlage);

                $report->setEigner($anlage->getEigner());

                $report->setMonth($month);

                $report->setYear($year);

                $dates = date('d.m.y', strtotime("01." . $month . "." . $year));
                $report->setStartDate(date_create_from_format('d.m.y', $dates));

                $dates = date('d.m.y', strtotime("30." . $month . "." . $year));
                $report->setEndDate(date_create_from_format('d.m.y', $dates));

                $report->setReportType("am-report");

                $report->setContentArray($output);

                $report->setRawReport("");

                $em->persist($report);

                $em->flush();


                //WE SHOULD REPLACE THIS FOR A COMMIT TO THE DB WITH THE NEW ENTITY(DEFINED IN MY NOTES)
                // specify the route to the binary.
                $pdf = new ChromePdf('/usr/bin/chromium');

                // Route where PDF will be saved.
                ///usr/www/users/pvpluy/dev.gs/PVplus-4.0
                $pos = $this->substr_Index($this->getParameter('kernel.project_dir'), '/', 5);
                $pathpart = substr($this->getParameter('kernel.project_dir'), $pos);
                $anlageName = $anlage->getAnlName();

                if ($month < 10) {
                    $month = '0' . $month;
                }

                $pdf->output('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlageName . '_AssetReport_' . $month . '_' . $year . '.pdf');
                $reportfile = fopen('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlageName . '_AssetReport_' . $month . '_' . $year . '.html', "w") or die("Unable to open file!");
                //cleanup html
                $pos = strpos($result, '<html>');
                fwrite($reportfile, substr($result, $pos));
                fclose($reportfile);

                #$pdf->generateFromHtml(substr($result, $pos));
                $pdf->generateFromFile('/usr/home/pvpluy/public_html' . $pathpart . '/public/' . $anlageName . '_AssetReport_' . $month . '_' . $year . '.html');
                $filename = $anlageName . '_AssetReport_' . $month . '_' . $year . '.pdf';
                $pdf->output($filename);

                // Header content type
                header("Content-type: application/pdf");
                header("Content-Length: " . filesize($filename));
                header("Content-type: application/pdf");

                // Send the file to the browser.
                readfile($filename);
            }
        }

        return $this->render('report/_form.html.twig',[
                'assetForm' => $form->createView()
        ]);
    }

    function substr_Index($str, $needle, $nth ): bool|int
    {
        $str2 = '';
        $posTotal = 0;
        for($i=0; $i < $nth; $i++){

            if($str2 != ''){
                $str = $str2;
            }

            $pos   = strpos($str, $needle);
            $str2  = substr($str, $pos+1);
            $posTotal += $pos+1;

        }
        return $posTotal-1;
    }
}
