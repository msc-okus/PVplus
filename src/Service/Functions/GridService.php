<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;
use DateTime;

class GridService
{
    public function __construct(
private PdoService $pdoService,
        private FunctionsService $functions
    )
    {
    }

    use G4NTrait;

    /**
     * Get Sum(power_prod) from 'Meters' Database.
     * By default we retriev the un filterd power
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @param false $ppc if true select only values if plant is not controlled ( p_set_gridop_rel = 100 AND p_set_rpc_rel = 100 )
     * @return float
     */
    public function getGridSum(Anlage $anlage, DateTime $from, DateTime $to, bool $ppc = false): float
    {
        $conn = $this->pdoService->getPdoPlant();
        $power = 0;

        if ($ppc){
            $sql = "SELECT sum(prod_power) as power_grid 
                FROM ".$anlage->getDbNameMeters() . " s
                RIGHT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp 
                WHERE s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' AND '" . $to->format('Y-m-d H:i') . "' AND s.prod_power > 0 AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is  null)";
        } else {
            $sql = "SELECT sum(prod_power) as power_grid 
                FROM ".$anlage->getDbNameMeters()." 
                WHERE stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' AND '" . $to->format('Y-m-d H:i') . "' AND prod_power > 0;";
        }
        $res = $conn->query($sql);
        if ($res->rowCount() === 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $power = (float)$row['power_grid'];
        }
        unset($res);

        return $power;
    }

    /**
     * Shortcut to get sum(power_prod from 'meters' DB if plant is not controlled
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @return float
     */
    public function getGridSumPpc(Anlage $anlage, DateTime $from, DateTime $to): float
    {
        return $this->getGridSum($anlage, $from, $to, true);
    }
}

