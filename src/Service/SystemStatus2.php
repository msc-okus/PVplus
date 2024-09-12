<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use Doctrine\ORM\NonUniqueResultException;
use PDO;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

class SystemStatus2
{
    use G4NTrait;

    private int $cacheLifetime = 900; // in sekunden (soll: 900)

    public function __construct(
        private readonly PdoService $pdoService,
        private readonly AvailabilityByTicketService $availability,
        private readonly CacheInterface $cache
    ){
    }

    /**
     * @throws InvalidArgumentException
     */
    public function systemstatus(Anlage $anlage): array
    {
        $result = [];
        $today = self::getCetTime();
        $yesterday = strtotime('- 1 day');

        $result['ioPlantData']      = $this->checkIOPlantData($anlage, $today);
        $result['ioWeatherData']    = $this->checkIOWeatherData($anlage, $today);
        $result['paToday']          = $this->checkPA($anlage, date('Y-m-d 00:15:00', $yesterday), date('Y-m-d H:i:s', $today));
        $result['expDiff']          = $this->checkExpDiff($anlage, date('Y-m-d 00:00:00', $yesterday), date('Y-m-d 23:59:00', $yesterday));

        return $result;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkIOPlantData(Anlage $anlage, $currentTimeStamp): array
    {
        return $this->cache->get('status_checkIOPlantData_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $currentTimeStamp) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            $conn = $this->pdoService->getPdoPlant();
            $result = [];
            $lastRecStampIst = 0;
            $lastDataStatus = '';

            $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameIst() . " WHERE e_z_evu > 0 OR wr_pac > 0 ORDER BY stamp DESC LIMIT 1");
            if ($res->rowCount() > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $lastRecStampIst = strtotime((string)$row->stamp);

                if ($currentTimeStamp - $lastRecStampIst <= $GLOBALS['abweichung']['io']['normal']) {
                    $lastDataStatus = 'normal';
                }
                if ($currentTimeStamp - $lastRecStampIst > $GLOBALS['abweichung']['io']['normal'] && $currentTimeStamp - $lastRecStampIst <= $GLOBALS['abweichung']['io']['warning']) {
                    $lastDataStatus = 'warning';
                }
                if ($currentTimeStamp - $lastRecStampIst > $GLOBALS['abweichung']['io']['warning']) {
                    $lastDataStatus = 'alert';
                }
            }
            $result['lastRecStampIst'] = date('Y-m-d H:i', $lastRecStampIst);
            $result['lastDataStatus'] = $lastDataStatus;

            return $result;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkIOWeatherData(Anlage $anlage, $currentTimeStamp): array
    {
        return $this->cache->get('status_checkIOWeatherData_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $currentTimeStamp) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            $conn = $this->pdoService->getPdoPlant();
            $result = [];
            $lastRecStampWeather = 0;
            $lastWeatherStatus = '';

            $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameWeather() . " WHERE g_upper + g_lower > 0 ORDER BY stamp DESC LIMIT 1");
            if ($res->rowCount() > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $lastRecStampWeather = strtotime((string)$row->stamp);
                if ($currentTimeStamp - $lastRecStampWeather <= $GLOBALS['abweichung']['io']['normal']) {
                    $lastWeatherStatus = 'normal';
                }
                if ($currentTimeStamp - $lastRecStampWeather > $GLOBALS['abweichung']['io']['normal'] && $currentTimeStamp - $lastRecStampWeather <= $GLOBALS['abweichung']['io']['warning']) {
                    $lastWeatherStatus = 'warning';
                }
                if ($currentTimeStamp - $lastRecStampWeather > $GLOBALS['abweichung']['io']['warning']) {
                    $lastWeatherStatus = 'alert';
                }
            }
            $result['lastRecStampIst'] = date('Y-m-d H:i', $lastRecStampWeather);
            $result['lastDataStatus'] = $lastWeatherStatus;

            return $result;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkPA(Anlage $anlage, $from, $to): array
    {
        return $this->cache->get('status_checkPA_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $from, $to) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            if ($anlage->getAnlType() === 'masterslave') {
                $result['pa'] = 0;
                $result['paStatus'] = '';
            } else {
                $result['pa'] = $this->availability->calcAvailability($anlage, date_create($from), date_create($to));
                if ($result['pa'] >= 99.0) $result['paStatus'] = 'normal';
                if ($result['pa'] >= 95.0 && $result['pa'] < 99.0) $result['paStatus'] = 'warning';
                if ($result['pa'] < 95.0) $result['paStatus'] = 'alert';
            }

            return $result;
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkExpDiff(Anlage $anlage, $from, $to): array
    {
        return $this->cache->get('status_checkExpDiff_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $from, $to) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            $result = [];

            $resultIst = $this->calcPowerIstAcAndDc($anlage, $from, $to);
            $resultAcAct = $resultIst['ac'];
            $resultDcAct = $resultIst['dc'];

            // ac und dc SOLL ermitteln (alles was da ist)
            // ////////////////
            $resultSoll = $this->calcPowerSollAcAndDc($anlage, $from, $to);
            $resultAcExp = $resultSoll['ac'];
            $resultDcExp = $resultSoll['dc'];

            $result['expDiffValue'] = $resultAcExp > 0 ? $resultAcAct * 100 / $resultAcExp : 0;
            if ($result['expDiffValue'] > 90) {
                $result['expDiffStatus'] = 'normal';
            } else if ($result['expDiffValue'] > 80) {
                $result['expDiffStatus'] = 'warning';
            } else {
                $result['expDiffStatus'] = 'alert';
            }

            return $result;
        });
    }

    /**
     * Ermitteln der Leitung einer Anlage für den angegebenen Zeitraum
     * Return Array mit AC Ist und DC Ist.
     * @throws InvalidArgumentException
     */
    private function calcPowerIstAcAndDc(Anlage $anlage, $from, $to): array
    {
        return $this->cache->get('status_calcPowerIstAcAndDc_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $from, $to) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            $conn = $this->pdoService->getPdoPlant();
            $returnArray['ac'] = 0;
            $returnArray['dc'] = 0;

            if ($anlage->getConfigType() === 3 || $anlage->getConfigType() === 4) {
                $res = $conn->query('SELECT sum(wr_pac) as SumPowerAC FROM ' . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to'");
                if ($res->rowCount() > 0) {
                    $row = $res->fetch(PDO::FETCH_OBJ);
                    $actAc = $row->SumPowerAC;
                    $returnArray['ac'] = $actAc;
                }
                $res = $conn->query('SELECT sum(wr_pdc) as SumPowerDC FROM ' . $anlage->getDbNameDcIst() . " WHERE stamp BETWEEN '$from' AND '$to'");
                if ($res) {
                    if ($res->rowCount() > 0) {
                        $row = $res->fetch(PDO::FETCH_OBJ);
                        $actDc = $row->SumPowerDC;
                        $returnArray['dc'] = $actDc;
                    }
                }
            } else { // Altes Datenbank Schema ( AC und DC ISt in einer Tabelle )
                $res = $conn->query("SELECT sum(wr_pac) as SumPowerAC, sum(wr_pdc) as SumPowerDC FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to'");
                if ($res) {
                    if ($res->rowCount() > 0) {
                        $row = $res->fetch(PDO::FETCH_OBJ);
                        $actAc = $row->SumPowerAC;
                        $actDc = $row->SumPowerDC;

                        $returnArray['ac'] = $actAc;
                        $returnArray['dc'] = $actDc;
                    }
                }
            }

            return $returnArray;
        });
    }

    /**
     * Ermitteln der Soll Leitung einer Anlage für den angegebenen Zeitraum
     * Return Array mit AC Soll und DC Soll.
     * @throws InvalidArgumentException
     */
    private function calcPowerSollAcAndDc(Anlage $anlage, $from, $to): array
    {
        return $this->cache->get('status_calcPowerSollAcAndDc_'.md5($anlage->getAnlId()), function(CacheItemInterface $cacheItem) use ($anlage, $from, $to) {
            $cacheItem->expiresAfter($this->cacheLifetime); // Lifetime of cache Item

            $conn = $this->pdoService->getPdoPlant();
            $returnArray['dc'] = 0;
            $returnArray['ac'] = 0;

            // Soll AC und DC
            $sql = 'SELECT sum(ac_exp_power) as SumPowerAC, sum(dc_exp_power) as SumPowerDC FROM ' . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to'";
            $res = $conn->query($sql);
            if ($res && $res->rowCount() > 0) {
                $row = $res->fetch(PDO::FETCH_OBJ);
                $returnArray['ac'] = $row->SumPowerAC;
                $returnArray['dc'] = $row->SumPowerDC;
            }

            return $returnArray;
        });
    }
}
