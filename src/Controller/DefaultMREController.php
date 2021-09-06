<?php

namespace App\Controller;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Helper\G4NTrait;
use App\Reports\Goldbeck\EPCMonthlyPRGuaranteeReport;
use App\Reports\Goldbeck\EPCMonthlyYieldGuaranteeReport;
use App\Repository\AnlagenRepository;
use App\Service\ExportService;
use App\Service\ReportEpcService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use PDO;

class DefaultMREController extends BaseController
{
    use G4NTrait;
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/mr/bavelse/export")
     */
    public function bavelseIrrExport(ExportService $bavelseExport, AnlagenRepository $anlagenRepository ): Response
    {
        $output = '';

        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);

        $output = $bavelseExport->gewichtetTagesstrahlung($anlage);

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
        $to   = date_create('2021-07-31');
        //$to   = date_create('2021-06-09');
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
        $from = date_create("$year-$month-1");
        $to   = date_create("$year-$month-$daysOfMonth");

        $output .= self::printArrayAsTable($export->getFacPRData($anlage, $from, $to));
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
     * @Route("/test/epc/report/{id}/{pdf}", defaults={"pdf"=false})
     * @deprecated
     */
    public function epcReport($id, $pdf, AnlagenRepository $anlagenRepository, ReportEpcService $reportEpc, EntityManagerInterface $em, NormalizerInterface $serializer)
    {
        $output = '';
        /** @var Anlage $anlage */
        $anlagen = $anlagenRepository->findIdLike([$id]);
        $anlage = $anlagen[0];
        $currentDate = date('Y-m-d H-i');
        $pdfFilename = 'EPC Report ' . $anlage->getAnlName() . ' - ' . $currentDate . '.pdf';
        $error = false;
        switch ($anlage->getEpcReportType()) {
            case 'prGuarantee' :
                $reportArray = $reportEpc->reportPRGuarantee($anlage);
                $report = new EPCMonthlyPRGuaranteeReport([
                    'headlines' => [
                        [
                            'projektNr'     => $anlage->getProjektNr(),
                            'anlage'        => $anlage->getAnlName(),
                            'eigner'        => $anlage->getEigner()->getFirma(),
                            'date'          => $currentDate,
                            'kwpeak'        => $anlage->getKwPeak(),
                        ],
                    ],
                    'main'          => $reportArray[0],
                    'forecast'      => $reportArray[1],
                    'pld'           => $reportArray[2],
                    'header'        => $reportArray[3],
                    'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                    'forecast_real' => $reportArray['prForecast'],
                    'formel'        => $reportArray['formel'],
                ]);
                break;
            case 'yieldGuarantee':
                $reportArray = $reportEpc->reportYieldGuarantee($anlage);

                $report = new EPCMonthlyYieldGuaranteeReport([
                    'headlines' => [
                        [
                            'projektNr'     => $anlage->getProjektNr(),
                            'anlage'        => $anlage->getAnlName(),
                            'eigner'        => $anlage->getEigner()->getFirma(),
                            'date'          => $currentDate,
                            'kwpeak'        => $anlage->getKwPeak(),
                        ],
                    ],
                    'main'          => $reportArray[0],
                    'forecast24'    => $reportArray[1],
                    'header'        => $reportArray[2],
                    'forecast_real' => $reportArray[3],
                    'legend'        => $serializer->normalize($anlage->getLegendEpcReports()->toArray(), null, ['groups' => 'legend']),
                ]);
                break;
            default:
                $error = true;
                $reportArray = [];
                $report = null;
        }

        if (!$error) {
            $output = $report->run()->render(true);

            // Speichere Report als 'epc-reprt' in die Report Entity
            if ($pdf) {
                $reportEntity = new AnlagenReports();
                $startDate = $anlage->getFacDateStart();
                $endDate = $anlage->getFacDate();
                $reportEntity
                    ->setCreatedAt(new \DateTime())
                    ->setAnlage($anlage)
                    ->setEigner($anlage->getEigner())
                    ->setReportType('epc-report')
                    ->setStartDate(self::getCetTime('object'))
                    ->setMonth(self::getCetTime('object')->sub(new \DateInterval('P1M'))->format('m'))
                    ->setYear(self::getCetTime('object')->format('Y'))
                    ->setEndDate($endDate)
                    ->setRawReport($output)
                    ->setContentArray($reportArray);
                $em->persist($reportEntity);
                $em->flush();
            }

            // erzeuge PDF mit CloudExport von KoolReport
            if ($pdf) {
                $secretToken = '2bf7e9e8c86aa136b2e0e7a34d5c9bc2f4a5f83291a5c79f5a8c63a3c1227da9';
                $settings = [
                    // 'useLocalTempFolder' => true,
                    'pageWaiting' => 'networkidle2', //load, domcontentloaded, networkidle0, networkidle2
                ];
                $report->run();
                $pdfOptions = [
                    'format'                => 'A4',
                    'landscape'             => true,
                    'noRepeatTableFooter'   => false,
                    'printBackground'       => true,
                    'displayHeaderFooter'   => true,
                ];
                $report->cloudExport()
                    ->chromeHeadlessio($secretToken)
                    ->settings($settings)
                    ->pdf($pdfOptions)
                    ->toBrowser($pdfFilename);
            }
        } else {
            $output = "<h1>Fehler: Es Ist kein Report ausgewählt.</h1>";
        }


        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'EPC Report',
            'availabilitys' => '',
            'output'        => $output,
        ]);

    }

    /**
     * @Route ("/test/olli")
     */
    public function olliExport()
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
            //dd($export);
            fputcsv($fp, $export,";");
        }

        fclose($fp);

        dd('');

        return $this->render('cron/showResult.html.twig', [
            'headline'      => 'EPC Report',
            'availabilitys' => '',
            'output'        => $result,
        ]);
    }
}


