<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Service\AvailabilityService;
use App\Service\ExportService;
use App\Service\FunctionsService;
use App\Service\ReportEpcService;
use App\Service\ReportsEpcNewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use PDO;

class DefaultMREController extends BaseController
{
    use G4NTrait;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route ("/mr/pa/{id}")
     */
    public function pa($id, AvailabilityService $availability, AnlagenRepository $anlagenRepository): Response
    {
        $anlage = $anlagenRepository->find($id);
        $date = "2020-12-07";
        $output = $availability->checkAvailability($anlage, strtotime($date), false);

        return $this->render('cron/showResult.html.twig', [
            'headline'      => "PA $date",
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }

    /**
     * @Route("/mr/bavelse/export")
     */
    public function bavelseExport(ExportService $bavelseExport, AnlagenRepository $anlagenRepository ): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);

        $from = date_create('2022-01-01');
        $to   = date_create('2022-01-31');
        $output = $bavelseExport->gewichtetTagesstrahlung($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'Systemstatus',
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }

    /**
     * @Route("/mr/export/rawdata/{id}")
     */
    public function exportRawDataExport($id, ExportService $bavelseExport, AnlagenRepository $anlagenRepository ): Response
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);

        $from = date_create('2021-01-01');
        $to   = date_create('2021-10-31');
        $output = $bavelseExport->getRawData($anlage, $from, $to);

        return $this->render('cron/showResult.html.twig', [
            'headline'      => $anlage->getAnlName() . ' RawData Export',
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }

    /**
     * @Route("/mr/export/facRawData/{id}/{year}/{month}")
     */
    public function exportFacRawDataExport($id, $month, $year, ExportService $export, AnlagenRepository $anlagenRepository ): Response
    {
        $output = '';

        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);

        $daysOfMonth = date('t', strtotime($year.'-'.$month.'-1'));


        $output .= self::printArrayAsTable($export->getFacPRData($anlage));
        $output .= "<hr>";
        //$output .= self::printArrayAsTable($export->getFacPAData($anlage, $from, $to));
        $output .= "<hr>";


        return $this->render('cron/showResult.html.twig', [
            'headline'      => $anlage->getAnlName() . ' FacData Export',
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }

    /**
     * @Route ("/test/olli")
     */
    public function olliExport(): Response
    {
        $conn = self::getPdoConnection();
        $sqlExp = "
        SELECT DATE_FORMAT(stamp, '%Y-%m-%d') as mystamp, 
            stamp,
            group_ac,
            round(sum(ac_exp_power),2) as soll, 
            round(sum(ac_exp_power_evu),2) as soll_evu, 
            round(sum(ac_exp_power_no_limit),2) as soll_nolimit
        FROM pvp_data.db__pv_dcsoll_AX102 WHERE stamp >= '2021-07-01 00:00' AND stamp <= '2021-07-31 23:59' GROUP by group_ac, stamp order by group_ac*1, stamp;
        ";

        $result = [];
        $expected = $conn->prepare($sqlExp);
        $expected->execute();

        foreach ($expected->fetchAll(PDO::FETCH_CLASS) as $row) {
            $sqlIst = "SELECT sum(wr_pac) as istsum FROM pvp_data.db__pv_ist_AX102 where wr_pac > 0 and stamp = '$row->stamp' and group_ac = $row->group_ac;";
            $ist = $conn->prepare($sqlIst);
            $ist->execute();
            $rowIst = $ist->fetch(PDO::FETCH_OBJ);

            if ($row->group_ac == 1) {
                $result[$row->stamp] = [
                    "stamp"                             => $row->stamp,
                    "soll-tr$row->group_ac"             => $row->soll,
                    "soll-nolimit-tr$row->group_ac"     => $row->soll_nolimit,
                    "ist-tr$row->group_ac"              => ($rowIst->istsum == null) ? 0 : $rowIst->istsum,
                ];
                $headlinesBase = [
                    "stamp"                             => 'stamp',
                    "soll-tr$row->group_ac"             => "soll-tr$row->group_ac",
                    "soll-nolimit-tr$row->group_ac"     => "soll-nolimit-tr$row->group_ac",
                    "ist-tr$row->group_ac"              => "ist-tr$row->group_ac",
                ];
            } else {
                $help[$row->stamp] = [
                    "stamp"                             => $row->stamp,
                    "soll-tr$row->group_ac"             => $row->soll,
                    "soll-nolimit-tr$row->group_ac"     => $row->soll_nolimit,
                    "ist-tr$row->group_ac"              => $row->istsum,
                ];
                $headlinesHelp = [
                    "stamp"                             => 'stamp',
                    "soll-tr$row->group_ac"             => "soll-tr$row->group_ac",
                    "soll-nolimit-tr$row->group_ac"     => "soll-nolimit-tr$row->group_ac",
                    "ist-tr$row->group_ac"              => "ist-tr$row->group_ac",
                ];
                $result[$row->stamp] = array_merge($result[$row->stamp], $help[$row->stamp]);
                $headlinesBase = array_merge($headlinesBase, $headlinesHelp);
            }
        }

        $fp = fopen("daten.csv", 'a');

        fputcsv($fp, $headlinesBase, ";");
        foreach ($result as $export) {
            fputcsv($fp, $export,";");
        }

        fclose($fp);

        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'EPC Report',
            'availabilitys' => '',
            'output'        => $result,
        ]);
    }

    /**
     * @Route ("/test/pa/{month}/{year}")
     */
    public function testPa($month, $year, AnlageAvailabilityRepository $availabilityRepository, Case5Repository $case5Repository, AnlagenRepository $anlagenRepository): Response
    {
        $output = '';
        $output2 = "<table>";
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => 84]);

        if ($anlage->getUseNewDcSchema()) {
            foreach ($anlage->getAcGroups() as $acGroup) {
                $inverterPowerDc[$acGroup->getAcGroup()] = $acGroup->getDcPowerInverter();
            }
        } else {
            foreach ($anlage->getAcGroups() as $acGroup) {
                ($acGroup->getDcPowerInverter() > 0) ? $powerPerInverter = $acGroup->getDcPowerInverter() / ($acGroup->getUnitLast() - $acGroup->getUnitFirst() + 1) : $powerPerInverter = 0;
                for ($inverter = $acGroup->getUnitFirst(); $inverter <= $acGroup->getUnitLast(); $inverter++) {
                    $inverterPowerDc[$inverter] = $powerPerInverter;
                }
            }
        }
        // Speichern der ermittelten Werte
        $lastDayInMonth = date("t", "$year-$month-01");
        $from   = date_create("$year-$month-01 00:00");
        $to     = date_create("$year-$month-$lastDayInMonth 23:59");
        $availabilitys = $availabilityRepository->sumAllCasesByDate($anlage, $from, $to);
        $sumPart1 = $sumPart2 = $sumPart3 = 0;
        foreach ($availabilitys as $row) {
            $inverter = $row['inverter'];
            // Berechnung der protzentualen Verfügbarkeit Part 1 und Part 2
            if ($row['control'] - $row['case4'] != 0) {
                /////////////////////
                $invAPart1 = (($row['case1'] + $row['case2']) / ($row['control'] - $row['case5'])) * 100;
                /////////////////////
                ($anlage->getPower() > 0 && $inverterPowerDc[$inverter] > 0) ? $invAPart2 = $inverterPowerDc[$inverter] / $anlage->getPower() : $invAPart2 = 1;
                $invAPart3 = $invAPart1 * $invAPart2;
            } else {
                $invAPart1 = 0;
                $invAPart2 = 0;
                $invAPart3 = 0;
            }
            $sumPart1 += $invAPart1;
            $sumPart2 += $invAPart2;
            $sumPart3 += $invAPart3;

            $output2 .= "<tr>
                    <td>Inverter: $inverter</td>
                    <td>Case1: ".$row['case1']." / ".$row['case1'] / 4 ."</td>
                    <td>Case2: ".$row['case2']." / ".$row['case2'] / 4 ."</td>
                    <td>Case3: ".$row['case3']." / ".$row['case3'] / 4 ."</td>
                    <td>Case4: ".$row['case4']." / ".$row['case4'] / 4 ."</td>
                    <td>Case5: ".$row['case5']." / ".$row['case5'] / 4 ."</td>
                    <td>Control: ".$row['control']." / ".$row['control'] / 4 ."</td></tr>";
            $output .= "Inverter: $inverter: PA Part 1: $invAPart1 | PA Part 2: $invAPart2 | PA Part 3: $invAPart3<br>";
        }
        $output2 .= "</table>";
        $summe = "<b>Summe: PA Part 1: $sumPart1 | PA Part 2: $sumPart2 | PA Part 3: $sumPart3</b><br>";

        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'Test PA',
            'availabilitys' => '',
            'output'        => $output2.$summe,
        ]);
    }

    /**
     * @Route ("/test/forcast")
     */
    public function testForcast(AnlagenRepository $anlagenRepository, FunctionsService $functions): Response
    {
        $output = '';
        $month = 11;

        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => 104]);

        $output .= "<h1>".$anlage->getAnlName()." - Monat: $month</h1>";
        $output .= $functions->getForcastByMonth($anlage, $month);

        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'Test Forcast',
            'availabilitys' => '',
            'output'        => $output,
        ]);
    }

    /**
     * @Route ("/test/epc/{id}/{raw}", defaults={"id"=93, "raw"=false})
     */
    public function testNewEpc($id, $raw, AnlagenRepository $anlagenRepository, FunctionsService $functions, ReportsEpcNewService $epcNew): Response
    {
        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => $id]);

        $date = date_create("2021-12-01");

        $monthTable = $epcNew->monthTable($anlage, $date);

        $forcastTable = $epcNew->forcastTable($anlage, $monthTable, $date);
        $chartYieldPercenDiff = $epcNew->chartYieldPercenDiff($anlage, $monthTable, $date);
        $chartYieldCumulativ = $epcNew->chartYieldCumulative($anlage, $monthTable, $date);

        $output = $functions->printArrayAsTable($forcastTable);
        $output .= $functions->print2DArrayAsTable($monthTable);

        if ($raw) {
            return $this->render('cron/showResult.html.twig', [
                'headline'      => 'Tabelle New EPC',
                'availabilitys' => '',
                'output'        => $output,
            ]);
        }
        return $this->render('report/epcReport.html.twig', [
            'anlage'            => $anlage,
            'monthsTable'       => $monthTable,
            'forcast'           => $forcastTable,
            'legend'            => $anlage->getLegendEpcReports(),
            'chart1'            => $chartYieldPercenDiff,
            'chart2'            => $chartYieldCumulativ,
        ]);
    }
}