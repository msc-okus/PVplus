<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Helper\ImportFunctionsTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\ApiConfigRepository;
use App\Repository\PVSystDatenRepository;
use App\Service;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use PDO;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImportHuaweiService
{
    use ImportFunctionsTrait;
    use G4NTrait;

    public function __construct(
        private readonly PdoService                   $pdoService,
        private readonly PVSystDatenRepository        $pvSystRepo,
        private readonly AnlagenRepository            $anlagenRepository,
        private readonly AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private readonly ApiConfigRepository          $apiConfigRepository,
        private readonly FunctionsService             $functions,
        private readonly EntityManagerInterface       $em,
        private readonly MeteoControlService          $meteoControlService,
        private readonly ManagerRegistry              $doctrine,
        private readonly WeatherServiceNew            $weatherService,
        private readonly externalApisService          $externalApis,
        private HttpClientInterface                   $client
    )
    {
        $thisApi = $this->externalApis;
    }

    /**
     * @throws NonUniqueResultException
     * @throws \JsonException
     * @throws Exception
     */
    public function prepareForImport(Anlage|int $anlage, $start, $end, string $importType = ""): void
    {
        //beginn collect params from plant
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
        }
        $plantId = $anlage->getAnlId();

        $groups = $anlage->getGroups();

        $anlageSensors = $anlage->getSensors();

        $apiconfigId = $anlage->getSettings()->getApiConfig();
        $apiconfig = $this->apiConfigRepository->findOneById($apiconfigId);
        $apiUser = $apiconfig->apiUser;
        $apiPassword = $this->unHashData($apiconfig->apiPassword);
        $apiToken = $apiconfig->apiToken;
        $apiType = $apiconfig->apiType;
        //beginn get Database Connections
        $DBDataConnection = $this->pdoService->getPdoPlant();
        //end get Database Connections
        date_default_timezone_set('Europe/Berlin');

        $baseUrl = "https://eu5.fusionsolar.huawei.com/thirdData/login";
        $postFileds = '
            {
                "userName": "' . $apiUser . '",
                "systemCode": "' . $apiPassword . '"
            }
        ';
        $headerFields = [
            "content-type: application/json",
            "Cookie: XSRF-TOKEN=" . $apiToken,
        ];

        $apiAccessToken = $this->externalApis->getAccessToken($this->client, $baseUrl, $postFileds, $headerFields, $apiType);

        $_SESSION['apiAccessToken'] = $apiAccessToken;

        $baseUrl = "https://eu5.fusionsolar.huawei.com/thirdData/getDevRealKpi";
        $headerFields = [
            "content-type: application/json",
            "Cookie: XSRF-TOKEN=" . $_SESSION['apiAccessToken'],
            "XSRF-TOKEN: " . $_SESSION['apiAccessToken'],
        ];

        for ($i = 0; $i <= count($groups) - 1; $i++) {
            $groupIds[] = $groups[$i]->getImportId();
        }

        $gropuIds = implode(",",$groupIds);

        $postFileds = '
            {
                        "devTypeId":1,
                        "devIds": "'.$gropuIds.'"
            }
        ';

        $data = $this->externalApis->getDataHuawai($this->client, $baseUrl, $headerFields, $postFileds);
        $resultReal = self::insertHuaweiData($groups, $data, $plantId);

        $tableName = "RealTimeData";
        self::insertData($tableName, $resultReal, $DBDataConnection);

        $baseUrl = "https://eu5.fusionsolar.huawei.com/thirdData/getDevRealKpi";
        $headerFields = [
            "content-type: application/json",
            "Cookie: XSRF-TOKEN=" . $apiAccessToken,
            "XSRF-TOKEN: " . $apiAccessToken,
        ];

        foreach ($anlageSensors as $sensor) {
            $sensorIds[] = $sensor->getVcomId();
        }

        $emiIds = implode(",",$sensorIds);

        $postFileds = '
            {
                "devTypeId":"10",
                "devIds": "'.$emiIds.'"
            }
        ';

        $data = $this->externalApis->getDataHuawai($this->client, $baseUrl, $headerFields, $postFileds);

        $resultRealEMI = self::insertHuaweiDataEMI($anlageSensors, $data, $plantId);

        $tableName = "RealTimeDataEMI";
        self::insertData($tableName, $resultRealEMI, $DBDataConnection);
    }

    public function saveToDb(Anlage|int $anlage)
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneByIdAndJoin($anlage);
        }
        $plantId = $anlage->getAnlId();
        $anlageSensors = $anlage->getSensors();
        $DBDataConnection = $this->pdoService->getPdoPlant();

        $i = 1;
        $j = 1;
        $k = 1;
        $countSensors = count($anlageSensors);

        $irrValueArray[0] = ['1'];
        $irrValueArray[1] = ['2'];
        #$irrValueArray[2] = ['3'];
        foreach ($anlageSensors as $sensor) {
            $sensorId = $sensor->getVcomId();
            if($i < 4){
                $irrValueArray[0][$i] = $sensorId;
            }
            if($i > 3 && $i < $countSensors){
                $irrValueArray[1][$j] = $sensorId;
                $j++;
            }
            if($i == $countSensors){
                #$irrValueArray[2][$k] = $sensorId;
                $jk++;
            }
            $i++;
        }

        $irrValueArray[0][$j] = 'East';
        $irrValueArray[0][$j+1] = 'upper';
        $irrValueArray[1][$j] = 'West';
        $irrValueArray[1][$j+1] = 'lower';
        #$irrValueArray[2][$k] = 'Temp';
        #$irrValueArray[2][$k+1] = 'temp';

        foreach ($irrValueArray as $irrval) {
            $sqlbc = "	SELECT STR_TO_DATE(timestampHR, '%Y,%m,%d %h,%i,%i') || substr('00' || ((cast(STR_TO_DATE(timestampHR, '%m') as int) / 15) * 15), -2) || ':00' period, count(*) counter, ID, TStamp ,timestampHR, avg(TotalRadiation) as irr, avg(AmbientTemp) as ATemp, avg(PanelTemp) as PTemp from RealTimeDataEMI WHERE `DeviceID` = '1000000035718179' or `DeviceID` = '1000000035718579' group by period order by TStamp";
            #$sqlbc = "SELECT * FROM RealTimeDataEMI";
            $resultbc = $DBDataConnection->query($sqlbc);

            while ($rowbc = $resultbc->fetch(PDO::FETCH_ASSOC)) {
                if ($rowbc['counter'] >= 1) {
                    if ($irrval[5] == 'upper') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate" => $rowbc['period'],
                            "stampa" => $rowbc['TStamp'],
                            "irr_upper" => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel" => round($rowbc['PTemp'], 2));
                    }
                    if ($irrval[5] == 'lower') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate" => $rowbc['period'],
                            "stampa" => $rowbc['TStamp'],
                            "irr_lower" => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel" => round($rowbc['PTemp'], 2));
                    }
                    if ($irrval[5] == 'temp') {
                        $irrarray[$rowbc['period']][] = array(
                            "sqldate" => $rowbc['period'],
                            "stampa" => $rowbc['TStamp'],
                            "irr_lower" => round($rowbc['irr'], 2),
                            "tempAmbient" => round($rowbc['ATemp'], 2),
                            "tempPanel" => round($rowbc['PTemp'], 2));
                    }
                }
            }
        }

        $tempPanel = "";
        $windSpeed = "";
        $irrHori  = "";
        $stamp = (string)date('Y-m-d H:i:00');
        foreach ($irrarray as $row => $val) {
            $xcount++;
            foreach($irrarray[$row] as $inval) {
                if (array_key_exists("irr_upper", $inval)){
                    $irr_upper = $inval["irr_upper"];
                }
                if (array_key_exists("irr_lower", $inval)){
                    $irr_lower = $inval["irr_lower"];
                }
                $tempAmbient = $inval["tempAmbient"];
                $tempPanel = $inval["tempPanel"];
            }

            #  echo "-> ( $xcount ) -- $weatherDbIdent, $row, $irr_upper, $irr_lower, $tempPanel, $tempAmbient, $windSpeed, $irrHori \n";
            $dataws[] = ["stamp"=>$stamp,"g_upper"=>$irr_upper,"g_lower"=>$irr_lower,"temp_pannel"=>$tempPanel,"temp_ambient"=>$tempAmbient,"wind_Speed"=>$windSpeed,"irr_hori"=>$irrHori];
            #  insertWeatherToWeatherDb($weatherDbIdent, $row, $irr_upper, $irr_lower, $tempPanel, $tempAmbient, $windSpeed, $irrHori);
        }


        $tableName = "db__pv_ws_CX$plantId";
        self::insertData($tableName, $dataws, $DBDataConnection);

    }

    public function unHashData(string $encodedData): string
    {
        return hex2bin(base64_decode($encodedData));
    }

}