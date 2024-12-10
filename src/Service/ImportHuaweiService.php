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
    public function prepareForImport(Anlage|int $anlage, string $importType = ""): void
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
        $useSensorsDataTable = $anlage->getSettings()->isUseSensorsData();
        $DBDataConnection = $this->pdoService->getPdoPlant();
        $DBBaseConnection = $this->pdoService->getPdoBase();

        self::writeinweatherdb($plantId, $anlageSensors, $useSensorsDataTable, $DBDataConnection);
        self::writeininverterdb($plantId, $DBDataConnection, $DBBaseConnection);
    }


    public function unHashData(string $encodedData): string
    {
        return hex2bin(base64_decode($encodedData));
    }

}