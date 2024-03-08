<?php

namespace App\Service\Charts;

use App\Entity\Anlage;
use App\Repository\AnlageAvailabilityRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\PdoService;
use DateTime;
use PDO;

class AvailabilityChartService
{
    public function __construct(
        private readonly AnlageAvailabilityRepository $availabilityRepository,
        private readonly AvailabilityByTicketService $availabilityByTicket,
        private readonly PdoService $pdoService,
    ){}

    /**
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     * @return array
     */
    public function getPlantAvailability(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $dataArray = [];
        $dataArray['availability'] = $this->availabilityRepository->findAvailabilityAnlageDate($anlage, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i'));
        foreach ($dataArray['availability'] as $key => $value) {
            $dataArray['availability'][$key]['invAPart10'] = $this->availabilityByTicket->calcInvAPart1($anlage,['case1' => $value['case10'], 'case2' => $value['case20'], 'case3' => $value['case30'], 'case5' => $value['case50'], 'control' => $value['control0']],0);
            $dataArray['availability'][$key]['invAPart11'] = $this->availabilityByTicket->calcInvAPart1($anlage,['case1' => $value['case11'], 'case2' => $value['case21'], 'case3' => $value['case31'], 'case5' => $value['case51'], 'control' => $value['control1']],1);
            $dataArray['availability'][$key]['invAPart12'] = $this->availabilityByTicket->calcInvAPart1($anlage,['case1' => $value['case12'], 'case2' => $value['case22'], 'case3' => $value['case32'], 'case5' => $value['case52'], 'control' => $value['control2']],2);
            $dataArray['availability'][$key]['invAPart13'] = $this->availabilityByTicket->calcInvAPart1($anlage,['case1' => $value['case13'], 'case2' => $value['case23'], 'case3' => $value['case33'], 'case5' => $value['case53'], 'control' => $value['control3']],3);

            $dataArray['availability'][$key]['invA0'] = $dataArray['availability'][$key]['invAPart10'] * $dataArray['availability'][$key]['invAPart20'];
            $dataArray['availability'][$key]['invA1'] = $dataArray['availability'][$key]['invAPart11'] * $dataArray['availability'][$key]['invAPart21'];
            $dataArray['availability'][$key]['invA2'] = $dataArray['availability'][$key]['invAPart12'] * $dataArray['availability'][$key]['invAPart22'];
            $dataArray['availability'][$key]['invA3'] = $dataArray['availability'][$key]['invAPart13'] * $dataArray['availability'][$key]['invAPart23'];
        }

        return $dataArray;
    }

    public function getPlantAvailabilityByIntervall(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $dataArray = [];

        $sql = "SELECT 
                    a.stamp as stamp,
                    b.pa0 as pa0,
                    b.pa1 as pa1,
                    b.pa2 as pa2,
                    b.pa3 as pa3,
                    b.g_lower as g_lower, 
                    b.g_upper as g_upper 
                FROM (db_dummysoll a LEFT JOIN ".$anlage->getDbNameWeather()." b ON a.stamp = b.stamp) 
                WHERE a.stamp > '".$from->format('Y-m-d H:i')."' and a.stamp <= '".$to->format('Y-m-d H:i')."'";
        $res = $conn->query($sql);
        if ($res->rowCount() > 0) {
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if ($anlage->getIsOstWestAnlage()) {
                    $irr = (((float)$row['g_upper'] * $anlage->getPowerEast() + (float)$row['g_lower'] * $anlage->getPowerWest()) / ($anlage->getPowerEast() + $anlage->getPowerWest()));
                } else {
                    $irr = (float)$row['g_upper'];
                }
                $dataArray['availability'][] = [
                    'stamp'  => $row['stamp'],
                    'pa0'   => (float)$row['pa0'],
                    'pa1'   => (float)$row['pa1'],
                    'pa2'   => (float)$row['pa2'],
                    'pa3'   => (float)$row['pa3'],
                    'irr'   => $irr,
                    'theoP_pa0' => $irr * $anlage->getPnom() * (float)$row['pa0'] / 4000,
                    'theoP_pa1' => $irr * $anlage->getPnom() * (float)$row['pa1'] / 4000,
                    'theoP_pa2' => $irr * $anlage->getPnom() * (float)$row['pa2'] / 4000,
                    'theoP_pa3' => $irr * $anlage->getPnom() * (float)$row['pa3'] / 4000,
                ];
            }
        }

        return $dataArray;
    }

}