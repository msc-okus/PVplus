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

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/mr/bavelse/export")
     */
    public function bavelseExport(ExportService $bavelseExport, AnlagenRepository $anlagenRepository ): Response
    {
        $output = '';

        /** @var Anlage $anlage */
        $anlage = $anlagenRepository->findOneBy(['anlId' => '97']);

        $from = date_create('2021-09-01');
        $to   = date_create('2021-09-30');
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
        $to   = date_create('2021-07-31');
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


