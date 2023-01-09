<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use App\Repository\Case5Repository;
use App\Repository\GridMeterDayRepository;
use App\Repository\MonthlyDataRepository;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ExternFileService
{
    use G4NTrait;

    public function __construct(
        private PVSystDatenRepository $pvSystRepo,
        private AnlagenRepository $anlagenRepository,
        private PRRepository $PRRepository,
        private AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private FunctionsService $functions,
        private EntityManagerInterface $em,
        private AvailabilityService $availabilityService
    )
    { }

    public function CallFileService(Anlage|int $anlage, string $day): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);
        }
            $timeStamp = strtotime($day);

            $from = date('Y-m-d 00:00', $timeStamp);
            $fromDate = date('Y-m-d', $timeStamp);
            $to = date('Y-m-d 23:59', $timeStamp);
            $day = date('Y-m-d', $timeStamp);
            $year = date('Y', $timeStamp);
            $month = date('m', $timeStamp);
            $anzTageUntilToday = (int)date('z', $timeStamp) + 1;
            $functionlist[] = ['anlagen_id' => '105','path' => './anlagen/InnaxNL/', 'script' => 'loadData.php' ];

            foreach ($functionlist as $item => $value){
                if ($value['anlagen_id'] === $anlage->getAnlagenId()){
                    exec("php ./anlagen/InnaxNL/loadData.php $fromDate > /dev/null &");
                    $output = "Success";
                  } else {
                    $output = "Nothing to do";
                }
            }

        return $output;
    }
}
