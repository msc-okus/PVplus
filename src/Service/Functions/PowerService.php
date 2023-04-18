<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Service\FunctionsService;
use PDO;
use DateTime;

class PowerService
{
    public function __construct(
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
        $conn = self::getPdoConnection();
        $power = 0;

        if ($ppc){
            $sql = "SELECT sum(prod_power) as power_grid 
                FROM ".$anlage->getDbNameMeters() . " s
                RIGHT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp 
                WHERE s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' 
                    AND '" . $to->format('Y-m-d H:i') . "' AND s.prod_power > 0 
                    AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                    AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null)"
            ;
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


    /**
     * Get sum from different AC Values from 'ist' Database.
     * By default we retrieve the unfiltered power (without ppc)
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @param bool $ppc
     * @return array
     */
    public function getSumAcPowerV2(Anlage $anlage, DateTime $from, DateTime $to, bool $ppc = false): array
    {
        $conn = self::getPdoConnection();
        $result = [];
        $powerEvu = $powerEGridExt = $powerExp = $powerExpEvu = $powerTheo = $tCellAvg = $tCellAvgMultiIrr = $powerAct = 0;

        // prüfe Ob Anlage PPC Steuerung hat, wenn nicht dann $ppc auf false setzten
        if (false === $anlage->getHasPPC()) $ppc = false;

        // SQL part zum auschluß von negativen 'evu' Werten
        $ignorNegativEvuSQL = $anlage->isIgnoreNegativEvu() ? 'AND e_z_evu > 0' : '';

        // SQL part für die einbindung der PPC Daten
        if ($ppc) {
            $ppcWhereSQL = " AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null) ";
            $ppcJoinSQL = " RIGHT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp ";
        } else {
            $ppcWhereSQL = $ppcJoinSQL = "";
        }

        // Wenn externe Tagesdaten genutzt werden, sollen lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
        // Ist nur möglich wenn KEINE ppc Daten ausgewertet werden
        if (!$ppc) $powerEGridExt = $this->functions->getSumeGridMeter($anlage, $from->format('Y-m-d'), $to->format('Y-m-d'),);

        // EVU Leistung ermitteln –
        // dieser Wert kann der offiziele Grid Zähler wert sein, kann aber auch nur ein interner Wert sein. Siehe Konfiguration $anlage->getUseGridMeterDayData()
        $sql = "SELECT sum(e_z_evu) as power_evu 
            FROM ".$anlage->getDbNameAcIst() . " s
            $ppcJoinSQL 
            WHERE unit = 1 AND s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' 
                AND '" . $to->format('Y-m-d H:i') . "' 
                $ignorNegativEvuSQL 
                $ppcWhereSQL"
        ;

        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvu = $row['power_evu'];
        }
        unset($res);

        $powerEvu = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvu, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i'));

        // Expected Leistung ermitteln
        $sql = 'SELECT SUM(ac_exp_power) AS sum_power_ac, SUM(ac_exp_power_evu) AS sum_power_ac_evu 
                    FROM '.$anlage->getDbNameDcSoll()." s
                    $ppcJoinSQL
                    WHERE s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' AND '" . $to->format('Y-m-d H:i') . "'
                    $ppcWhereSQL
                    ";

        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerExpPpc = $row['sum_power_ac'];
            $powerExpEvuPpc = $row['sum_power_ac_evu'];
        }
        unset($res);

        // Theoretic Power (TempCorr)
        $sql = 'SELECT SUM(theo_power) AS theo_power 
                    FROM '.$anlage->getDbNameAcIst()." s
                    $ppcJoinSQL
                    WHERE s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' AND '" . $to->format('Y-m-d H:i') . "'
                    AND theo_power > 0
                    $ppcWhereSQL
                    ";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerTheoPpc = $row['theo_power'];
        }
        unset($res);


        // Actual (Inverter Out) Leistung ermitteln
        $sql = 'SELECT sum(wr_pac) as sum_power_ac, sum(theo_power) as theo_power 
                    FROM '.$anlage->getDbNameAcIst()." s
                    $ppcJoinSQL
                    WHERE s.stamp BETWEEN '" . $from->format('Y-m-d H:i') . "' AND '" . $to->format('Y-m-d H:i') . "'
                    AND wr_pac > 0
                    $ppcWhereSQL
                    ";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerActPpc = $row['sum_power_ac'];
        }
        unset($res);

        $result['powerEvu']             = (float)$powerEvu;
        $result['powerAct']             = (float)$powerAct;
        $result['powerExp']             = (float)$powerExp;
        $result['powerExpEvu']          = (float)$powerExpEvu;
        $result['powerEGridExt']        = $ppc ? (float)$powerEvu : (float)$powerEGridExt;
        $result['powerTheo']            = (float)$powerTheo;
        $result['tCellAvg']             = (float)$tCellAvg;
        $result['tCellAvgMultiIrr']     = (float)$tCellAvgMultiIrr;

        return $result;
    }

    /**
     * Get sum from different AC Values from 'ist' Database.
     * Sum only values with ppc = 100
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     */
    public function getSumAcPowerV2Ppc(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        return $this->getSumAcPowerV2($anlage, $from, $to, true);
    }



    public function checkAndIncludeMonthlyCorrectionEVU(Anlage $anlage, ?float $evu, $from, $to): ?float
    {
        $conn = self::getPdoConnection();

        $fromObj = date_create($from);
        $toObj = date_create($to);
        if ($evu) {
            if ($anlage->getUseGridMeterDayData() === false) {
                $monthlyDatas = $this->monthlyDataRepo->findByDateRange($anlage, $fromObj, $toObj);
                $countMonthes = count($monthlyDatas);
                #if ($countMonthes > 1) dump($monthlyDatas, $evu);

                foreach ($monthlyDatas as $monthlyData) {
                    // calculate the first and the last day of the given month and year in $monthlyData
                    $firstDayMonth = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-01");
                    $lastDayMonth  = date_create($monthlyData->getYear() . "-". $monthlyData->getMonth()."-".$firstDayMonth->format("t"));

                    // check if the time period is the hole month. Only if we get 1 whole Month we can use this correction
                    // or if we get the starting or ending Month from an epc Report ($epcStartEndMonth == true)

                    $epcStartMonth = $anlage->getEpcReportStart()->format('Ym') ===  $firstDayMonth->format('Ym');
                    $epcEndMonth   = $anlage->getEpcReportEnd()->format('Ym') ===  $firstDayMonth->format('Ym');
                    $wholeMonth = ($toObj->getTimestamp() - $fromObj->getTimestamp()) / 86400 >= 28; // looks like this is not only one Day
                    $wholeReport = $anlage->getEpcReportStart()->format('Ymd') === $fromObj->format('Ymd') && $anlage->getEpcReportEnd()->format('Ymd') === $toObj->format('Ymd');
                    #if ($countMonthes > 1) dump($wholeMonth);
                    if (($firstDayMonth->format("Y-m-d 00:00") === $from && $lastDayMonth->format("Y-m-d 23:59") === $to) || $epcStartMonth || $epcEndMonth || $wholeReport || $wholeMonth) {
                        if ($monthlyData->getExternMeterDataMonth() && $monthlyData->getExternMeterDataMonth() > 0) {
                            if ($epcStartMonth) {
                                $tempFrom = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $anlage->getFacDateStart()->format('d') . ' 00:00');
                            } else {
                                $tempFrom = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-01 00:00');
                            }
                            $tempDaysInMonth = $tempFrom->format('t');
                            if ($epcEndMonth) {
                                $tempTo = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $anlage->getFacDate()->format('d') . ' 23:59');
                            } else {
                                $tempTo = new DateTime($monthlyData->getYear() . '-' . $monthlyData->getMonth() . '-' . $tempDaysInMonth . ' 23:59');
                            }
                            if ($anlage->isIgnoreNegativEvu()) {
                                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '" . $tempFrom->format('Y-m-d H:i') . "' AND '" . $tempTo->format('Y-m-d H:i') . "' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
                            } else {
                                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '" . $tempFrom->format('Y-m-d H:i') . "' AND '" . $tempTo->format('Y-m-d H:i') . "' GROUP BY unit LIMIT 1";
                            }
                            #if ($countMonthes > 1) dump($sql. "||| $countMonthes");
                            $res = $conn->query($sql);
                            if ($res->rowCount() == 1) {
                                $row = $res->fetch(PDO::FETCH_ASSOC);
                                $evu -= $row['power_evu'];
                                $evu += $monthlyData->getExternMeterDataMonth();
                                #if ($countMonthes > 1) dump($monthlyData->getMonth(), $row['power_evu'], $monthlyData->getExternMeterDataMonth());
                            }
                            unset($res);
                        }
                    }
                }
            }
        }

        return $evu;
    }

}

