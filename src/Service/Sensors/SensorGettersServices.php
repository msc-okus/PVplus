<?php

namespace App\Service\Sensors;

use App\Entity\Anlage;
use App\Service\PdoService;
use DateTime;
use PDO;

class SensorGettersServices
{
    public function __construct(
        private readonly PdoService $pdoService,
    ) {
    }

    /**
     *
     * @param Anlage $anlage
     * @param DateTime $from
     * @param DateTime $to
     * @return array
     */
    public function getSensorsIrrByTime(Anlage $anlage, DateTime $from, DateTime $to): array
    {
        $conn = $this->pdoService->getPdoPlant();
        $sensors = $anlage->getSensors()->toArray();

        $fromSQL = $from->format('Y-m-d H:i');
        $toSQL = $to->format('Y-m-d H:i');
        $resultArray = [];
        $sql = "SELECT * FROM " . $anlage->getDbNameSensorsData() . " WHERE stamp >= '$fromSQL' AND stamp <= '$toSQL' order by stamp, id_sensor;";
        $result = $conn->query($sql);

        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                foreach ($sensors as $sensor) {
                    $resultArray[$row['stamp']][$sensor->getNameShort()] = $row['value'];
                }
            }
        }

        return $resultArray;
    }
}