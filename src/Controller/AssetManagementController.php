<?php

namespace App\Controller;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use Nuzkito\ChromePdf\ChromePdf;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AssetManagementController extends BaseController
{
    use G4NTrait;

    private string $kernelProjectDir;

    public function __construct(string $kernelProjectDir = '')
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @param $doctype ( 0 = PDF, 1 = Excel, 2 = PNG (Grafiken) )
     * @param $charttypetoexport (0 = , 1 = )
     *
     * @deprecated
     */
    #[Route(path: '/asset/report/{id}/{month}/{year}/{export}/{pages}', name: 'report_asset_management')]
    public function assetReport($id, $month, $year, $export, $pages, AssetManagementService $assetManagement, AnlagenRepository $anlagenRepository, Request $request)
    {
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);
        $output = $assetManagement->assetReport($anlage, $month, $year, $pages);
        $baseurl = $request->getSchemeAndHttpHost();
        $plantId = $output['plantId'];
        $result = $this->render('report/assetreport.html.twig', [
            'baseurl' => $baseurl,
            'owner' => $output['owner'],
            'plantSize' => $output['plantSize'],
            'plantName' => $output['plantName'],
            'anlGeoLat' => $output['anlGeoLat'],
            'anlGeoLon' => $output['anlGeoLon'],
            'year' => $output['year'],
            'month' => $output['month'],
            'reportmonth' => $output['reportmonth'],
            'customer_logo' => $baseurl.'/goldbeck/reports/asset_management/goldbecksolar_logo.svg',
            'font_color' => '#9aacc3',
            'font_color_second' => '#2e639a',
            'font_color_third' => '#36639c',
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
            'useGridMeterDayData' => $output['useGridMeterDayData'],
            'showAvailability' => $output['showAvailability'],
            'showAvailabilitySecond' => $output['showAvailabilitySecond'],
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
        if ($export == 0) {
            return $result;
        } else {
            // specify the route to the binary.
            $pdf = new ChromePdf('/usr/bin/chromium');

            // Route when PDF will be saved.
            // /usr/www/users/pvpluy/dev.gs/PVplus-4.0
            $pos = $this->substr_Index($this->kernelProjectDir, '/', 5);
            $pathpart = substr($this->kernelProjectDir, $pos);
            $anlageName = $anlage->getAnlName();

            if ($month < 10) {
                $month = '0'.$month;
            }

            $pdf->output('/usr/home/pvpluy/public_html'.$pathpart.'/public/'.$anlageName.'_AssetReport_'.$month.'_'.$year.'.pdf');
            $reportfile = fopen('/usr/home/pvpluy/public_html'.$pathpart.'/public/'.$anlageName.'_AssetReport_'.$month.'_'.$year.'.html', 'w') or exit('Unable to open file!');
            // cleanup html
            $pos = strpos($result, '<html>');
            fwrite($reportfile, substr($result, $pos));
            fclose($reportfile);

            // $pdf->generateFromHtml(substr($result, $pos));
            $pdf->generateFromFile('/usr/home/pvpluy/public_html'.$pathpart.'/public/'.$anlageName.'_AssetReport_'.$month.'_'.$year.'.html');
            $filename = $anlageName.'_AssetReport_'.$month.'_'.$year.'.pdf';
            $pdf->output($filename);
            // Header content type
            header('Content-type: application/pdf');
            header('Content-Length: '.filesize($filename));
            header('Content-type: application/pdf');

            // Send the file to the browser.
            readfile($filename);
            // return $result;
        }
    }

    private function substr_Index($str, $needle, $nth)
    {
        $str2 = '';
        $posTotal = 0;
        for ($i = 0; $i < $nth; ++$i) {
            if ($str2 != '') {
                $str = $str2;
            }

            $pos = strpos($str, $needle);
            $str2 = substr($str, $pos + 1);
            $posTotal += $pos + 1;
        }

        return $posTotal - 1;
    }
}
