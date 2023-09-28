<?php

namespace App\Service\Functions;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use App\Helper\G4NTrait;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\TicketDateRepository;
use App\Service\FunctionsService;
use PDO;
use App\Service\PdoService;
use DateTime;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;

class PowerService
{
    public function __construct(
private readonly PdoService $pdoService,
        private readonly FunctionsService $functions,
        private readonly MonthlyDataRepository $monthlyDataRepo,
        private readonly GridMeterDayRepository $gridMeterDayRepo,
        private readonly TicketDateRepository $ticketDateRepo
    )
    {
    }

    use G4NTrait;

    /**
     * Get Sum(power_prod) from 'Meters' Database.
     * By default we retriev the un filterd power
     *
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
                LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp 
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
     *
     * @param int|null $inverterID
     * @return array
     * @throws \Exception
     */
    public function getSumAcPowerV2(Anlage $anlage, DateTime $from, DateTime $to, bool $ppc = false, ?int $inverterID = null): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $result = [];
        $powerEvu = $powerExp = $powerExpEvu = $powerEGridExt = $powerTheo = $powerTheoNoPpc = $tCellAvg = $tCellAvgMultiIrr = 0;

        $ignorNegativEvuSQL = $anlage->isIgnoreNegativEvu() ? 'AND e_z_evu > 0' : '';
        $ppcSQLpart1 = $ppcSQLpart2 = $ppcSQLpart1Meters = '';
        if ($ppc && $anlage->getUsePPC()){
            $ppcSQLpart1 = "LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp";
            $ppcSQLpart1Meters = "LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp";
            $ppcSQLpart2 = " AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) 
                AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null)";
        }

        // Wenn externe Tagesdaten genutzt werden, sollen lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
        // $powerEGridExt = $this->functions->getSumeGridMeter($anlage, $from, $to);

        // EVU / Grid Leistung ermitteln –
        // dieser Wert soll der offiziele Grid Zähler Wert sein, wir in naher Zukunft durch die Daten aus 'meters' ersetzt werden müssen
        if ($inverterID === null) {
            if ($anlage == '97') { // Power Data liegt in 'Meters' (db__pv_meters_xxx) Datei
                // Bavelse Berg = Anlage ID 97
                $sql = "SELECT sum(prod_power) as power_grid 
            FROM " . $anlage->getDbNameMeters() . " s
            $ppcSQLpart1Meters 
            WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' 
                AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' 
                $ppcSQLpart2";

                $res = $conn->query($sql);
                if ($res->rowCount() === 1) {
                    $row = $res->fetch(PDO::FETCH_ASSOC);
                    $powerEvu = $row['power_grid'];
                }
            } else {
                // Wenn externe Tagesdaten genutzt werden, sollen lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
                if ($anlage->getUseGridMeterDayData()) {
                    // Berechnung der externen Zählerwerte unter Berücksichtigung der Manuel eingetragenen Monatswerte.
                    // Darüber kann eine Koorektur der Zählerwerte erfolgen.
                    // Wenn für einen Monat Manuel Zählerwerte eingegeben wurden, wird der Wert der Tageszählwer wieder subtrahiert und der Manuel eingebene Wert addiert.
                    $powerEGridExt = $this->gridMeterDayRepo->sumByDateRange($anlage, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i'));
                    if (!$powerEGridExt) $powerEGridExt = 0;
                    $powerEGridExt = $this->correctGridByTicket($anlage, $powerEGridExt, $from, $to); // Function not fianly tested
                }

                // EVU Leistung ermitteln –
                // dieser Wert kann der offiziele Grid Zähler wert sein, kann aber auch nur ein interner Wert sein. Siehe Konfiguration $anlage->getUseGridMeterDayData()
                if ($anlage->isIgnoreNegativEvu()) {
                    $sql = 'SELECT sum(e_z_evu) as power_evu 
                        FROM ' . $anlage->getDbNameAcIst() . " s
                        $ppcSQLpart1 
                        WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' AND s.e_z_evu > 0 
                        $ppcSQLpart2
                        GROUP BY s.unit LIMIT 1";
                } else {
                    $sql = 'SELECT sum(e_z_evu) as power_evu 
                        FROM ' . $anlage->getDbNameAcIst() . " s
                        $ppcSQLpart1 
                        WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' 
                        $ppcSQLpart2
                        GROUP BY s.unit LIMIT 1";
                }
                $res = $conn->query($sql);
                if ($res->rowCount() == 1) {
                    $row = $res->fetch(PDO::FETCH_ASSOC);
                    $powerEvu = $row['power_evu'];
                }
            }
            unset($res);
            #$powerEvu = $this->checkAndIncludeMonthlyCorrectionEVU($anlage, $powerEvu, $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i'));
            $powerEvu = $this->correctGridByTicket($anlage, $powerEvu, $from, $to);
        } else {
            $powerEvu = null;
        }

        if ($inverterID !== null){
            $sqlPartInverter = "AND group_dc = $inverterID"; // we must change this
        } else {
            $sqlPartInverter = "";
        }
        // Expected Leistung ermitteln
        $sql = 'SELECT SUM(ac_exp_power) AS sum_power_ac, SUM(ac_exp_power_evu) AS sum_power_ac_evu FROM '.$anlage->getDbNameDcSoll()." WHERE stamp >= '" . $from->format('Y-m-d H:i') . "' AND stamp <= '" . $to->format('Y-m-d H:i') . "' $sqlPartInverter";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerExp = $row['sum_power_ac'];
            $powerExpEvu = $row['sum_power_ac_evu'];
        }
        unset($res);

        if ($inverterID !== null){
            $sqlPartInverter = "AND unit = $inverterID";
        } else {
            $sqlPartInverter = "";
        }
        // Theoretic Power (TempCorr)
        $sql = 'SELECT SUM(theo_power) AS theo_power 
                FROM '.$anlage->getDbNameAcIst()."  s
                $ppcSQLpart1 
                WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' AND s.theo_power > 0 $ppcSQLpart2 $sqlPartInverter";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerTheo = $row['theo_power'];
        }
        unset($res);

        // Theoretic Power (TempCorr)
        $sql = 'SELECT SUM(theo_power) AS theo_power 
                FROM '.$anlage->getDbNameAcIst()."  s 
                WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' AND s.theo_power > 0 $sqlPartInverter";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerTheoNoPpc = $row['theo_power'];
        }
        unset($res);

        // Actual (Inverter Out) Leistung ermitteln
        $sql = 'SELECT sum(wr_pac) as sum_power_ac
                FROM '.$anlage->getDbNameAcIst()." s
                $ppcSQLpart1 
                WHERE s.stamp >= '" . $from->format('Y-m-d H:i') . "' AND s.stamp <= '" . $to->format('Y-m-d H:i') . "' AND s.wr_pac > 0 $ppcSQLpart2 $sqlPartInverter";
        $res = $conn->query($sql);

        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $result['powerEvu']         = (float) $powerEvu;
            $result['powerAct']         = (float) $row['sum_power_ac'];
            $result['powerExp']         = (float) $powerExp;
            $result['powerExpEvu']      = (float) $powerExpEvu;
            $result['powerEGridExt']    = (float) $powerEGridExt;
            $result['powerTheo']        = (float) $powerTheo;
            $result['powerTheoNoPpc']   = (float) $powerTheoNoPpc;
            $result['tCellAvg']         = (float) $tCellAvg;
            $result['tCellAvgMultiIrr'] = (float) $tCellAvgMultiIrr;
        }
        unset($res);

        return $result;
    }

    /**
     * Get sum from different AC Values from 'ist' Database.
     * Sum only values with ppc = 100
     *
     * @param $from
     * @param $to
     * @param int|null $inverterID
     * @return array
     * @throws \Exception
     */
    public function getSumAcPowerV2Ppc(Anlage $anlage, $from, $to, ?int $inverterID = null): array
    {
        return $this->getSumAcPowerV2($anlage, $from, $to, true, $inverterID);
    }


    /**
     * schold be removed and replaced by correction by Tickets
     * @param float|null $evu
     * @param $from
     * @param $to
     * @return float|null
     * @throws \Exception
     */
    #[Deprecated]
    public function checkAndIncludeMonthlyCorrectionEVU(Anlage $anlage, ?float $evu, $from, $to): ?float
    {
        $conn = $this->pdoService->getPdoPlant();

        $fromObj = date_create($from);
        $toObj = date_create($to);
        if ($evu) {
            if ($anlage->getUseGridMeterDayData() === false) {
                $monthlyDatas = $this->monthlyDataRepo->findByDateRange($anlage, $fromObj, $toObj);
                $countMonthes = is_countable($monthlyDatas) ? count($monthlyDatas) : 0;

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

                            $res = $conn->query($sql);
                            if ($res->rowCount() == 1) {
                                $row = $res->fetch(PDO::FETCH_ASSOC);
                                $evu -= $row['power_evu'];
                                $evu += $monthlyData->getExternMeterDataMonth();
                            }
                            unset($res);
                        }
                    }
                }
            }
        }

        return $evu;
    }

    public function correctGridByTicket(Anlage $anlage, ?float $evu, DateTime $startDate, DateTime $endDate): ?float
    {
        $conn = $this->pdoService->getPdoPlant();

        // Suche alle Tickets (Ticketdates) die in den Zeitraum fallen
        // Es werden Nur Tickets mit Energy exclude Bezug gesucht (Performance Tickets mit ID = 72 + ??
        $ticketArray = $this->ticketDateRepo->performanceTicketsExcludeEnergy($anlage, $startDate, $endDate);

        // Dursuche alle Tickets in Schleife
        // berechne Wert aus Original Daten und Subtrahiere vom Wert
        // berechne ersatz Wert und Addiere zum entsprechenden Wert
         /** @var TicketDate $ticketArray */
        foreach ($ticketArray as $ticket) { #loop über query result
            // Start und End Zeitpunkt ermitteln, es sollen keine Daten gesucht werden die auserhalb des Übergebenen Zeitaums liegen.
            // Ticket kann ja schon vor dem Zeitraum gestartet oder danach erst beendet werden
            if ($startDate > $ticket->getBegin()){
                $tempoStartDate = $startDate;
            } else {
                $tempoStartDate = $ticket->getBegin();
            }
            if ($endDate < $ticket->getEnd()){
                $tempoEndDate = $endDate;
            } else {
                $tempoEndDate = $ticket->getEnd();
            }

            // Suche und summiere Werte in AC Ist Tabelle
            if ($anlage->isIgnoreNegativEvu()) {
                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp >= '" . $tempoStartDate->format('Y-m-d H:i') . "' AND stamp < '" . $tempoEndDate->format('Y-m-d H:i') . "' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
            } else {
                $sql = 'SELECT sum(e_z_evu) as power_evu FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp >= '" . $tempoStartDate->format('Y-m-d H:i') . "' AND stamp < '" . $tempoEndDate->format('Y-m-d H:i') . "' GROUP BY unit LIMIT 1";
            }
            $res = $conn->query($sql);
            if ($res->rowCount() == 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $evu -= $row['power_evu']; // ermittelten Wert vom gesamt evu abziehen
            }
        }



        return $evu;
    }

    /**
     * Wird für den Bericht Bavelse Berg genutzt
     *
     * @param $from
     * @param $to
     * @param $section
     * @return array
     */
    public function getSumAcPowerBySection(Anlage $anlage, $from, $to, $section): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $result = [];
        $powerEvu = $powerEvuPpc = $powerAct = $powerTheo = $powerTheoFt = 0;
        $powerExp = $powerExpEvu = $powerTheoPpc = $powerTheoFtPpc = 0;

        // ############ für den angeforderten Zeitraum #############

        // Wenn externe Tagesdaten genutzt werden sollen, lade diese aus der DB und ÜBERSCHREIBE die Daten aus den 15Minuten Werten
        if ($anlage->getUseGridMeterDayData()) {
            $year = date('Y', strtotime((string) $from));
            $month = date('m', strtotime((string) $from));
            $monthlyData = $this->monthlyDataRepo->findOneBy(['anlage' => $anlage, 'year' => $year, 'month' => $month]);
            if ($monthlyData != null && $monthlyData->getExternMeterDataMonth() > 0) {
                // Es gibt keine tages Daten des externen Grid Zählers
                $powerEGridExt = $monthlyData->getExternMeterDataMonth();
            } else {
                $powerEGridExt = $this->gridMeterDayRepo->sumByDateRange($anlage, $from, $to);
            }
        } else {
            $powerEGridExt = 0;
        }

        // EVU Leistung ermitteln – kann aus unterschiedlichen Quellen kommen
        $sql = 'SELECT sum(e_z_evu) as power_evu FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' AND e_z_evu > 0 GROUP BY unit LIMIT 1";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvu = $row['power_evu'];
        }
        unset($res);

        // EVU Leistung ermitteln – nur EVU aber PPC bereinigt
        $sql = "SELECT sum(e_z_evu) as power_evu_ppc
                FROM " . $anlage->getDbNameAcIst() . " s
                LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp 
                WHERE s.stamp >= '$from' AND s.stamp <= '$to' AND s.unit = $section AND s.e_z_evu > 0 AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is  null)";
        $res = $conn->query($sql);
        if ($res->rowCount() === 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerEvuPpc = $row['power_evu_ppc'];
        }
        unset($res);

        // Expected Leistung ermitteln
        $sql = 'SELECT sum(ac_exp_power) as sum_power_ac, sum(ac_exp_power_evu) as sum_power_ac_evu FROM '.$anlage->getDbNameDcSoll()." WHERE stamp >= '$from' AND stamp <= '$to' AND group_ac = $section";
        $res = $conn->query($sql);
        if ($res->rowCount() === 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerExp = $row['sum_power_ac'];
            $powerExpEvu = $row['sum_power_ac_evu'];
        }
        unset($res);

        // Actual (Inverter Out) Leistung ermitteln
        $sql = 'SELECT sum(wr_pac) as sum_power_ac FROM '.$anlage->getDbNameAcIst()." WHERE stamp >= '$from' AND stamp <= '$to' AND group_ac = $section AND wr_pac > 0";
        $res = $conn->query($sql);
        if ($res->rowCount() === 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerAct = $row['sum_power_ac'];
        }

        // Theo Power without PPC
        $sql = "SELECT sum(theo_power) as theo_power, sum(theo_power_ft) as theo_power_ft FROM ".$anlage->getDbNameSection()." WHERE stamp >= '$from' AND stamp <= '$to' AND `section` = $section AND theo_power_ft > 0";
        $res = $conn->query($sql);
        if ($res->rowCount() == 1) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $powerTheo = $row['theo_power'];
            $powerTheoFt = $row['theo_power_ft'];
        }
        unset($res);

        // Theo Power WITH PPC
        if ($anlage->getHasPPC()) {
            $sql = "SELECT sum(theo_power) as theo_power, sum(theo_power_ft) as theo_power_ft 
                FROM " . $anlage->getDbNameSection() . " s
                LEFT JOIN " . $anlage->getDbNamePPC() . " ppc ON s.stamp = ppc.stamp 
                WHERE s.stamp >= '$from' AND s.stamp <= '$to' AND s.section = $section AND s.theo_power_ft > 0 AND (ppc.p_set_gridop_rel = 100 OR ppc.p_set_gridop_rel is null) AND (ppc.p_set_rpc_rel = 100 OR ppc.p_set_rpc_rel is null)";
            $res = $conn->query($sql);
            if ($res->rowCount() === 1) {
                $row = $res->fetch(PDO::FETCH_ASSOC);
                $powerTheoPpc = $row['theo_power'];
                $powerTheoFtPpc = $row['theo_power_ft'];
            }
            unset($res);
        }

        $result['powerEvu'] = $powerEvu;
        $result['powerEvuPpc'] = $powerEvuPpc;
        $result['powerAct'] = $powerAct;
        $result['powerExp'] = $powerExp;
        $result['powerExpEvu'] = $powerExpEvu;
        $result['powerEGridExt'] = $powerEGridExt;
        $result['powerTheo'] = $powerTheo;
        $result['powerTheoFt'] = $powerTheoFt;
        $result['powerTheoPpc'] = $powerTheoPpc;
        $result['powerTheoFtPpc'] = $powerTheoFtPpc;

        return $result;
    }
}

