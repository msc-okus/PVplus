<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenRepository;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use App\Repository\PRRepository;
use App\Repository\PVSystDatenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ExternFileService
{
    use G4NTrait;

    public function __construct(
        private PdoService $pdoService,
        private PVSystDatenRepository $pvSystRepo,
        private AnlagenRepository $anlagenRepository,
        private PRRepository $PRRepository,
        private AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private FunctionsService $functions,
        private EntityManagerInterface $em,
        private AvailabilityService $availabilityService,
        private $kernelProjectDir
    )
    { }

    public function CallFileServiceAPI(Anlage|int $anlage, string $day): string
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
    public function CallFileServiceINAX(Anlage|int $anlage, string $day): string
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

        $makefile   = true;
        if ($makefile == true) {
            $filesystem = new Filesystem();
            try {
                $realpath = $filesystem->tempnam('/tmp', '' );
                $jsondata = json_encode($functionlist);
                file_put_contents($realpath, $jsondata);
            } catch (IOExceptionInterface $exception) {
                echo "An error occurred while creating your directory at " . $exception->getPath();
            }
        }

        foreach ($functionlist as $item => $value) {
            if ($value['anlagen_id'] === $anlage->getAnlagenId()){
                exec("php ./anlagen/InnaxNL/loadData.php $fromDate > /dev/null &");
                $output = "Success";
            } else {
                $output = "Nothing to do";
            }
        }
        return $output;
    }

    public function callImportDataFromApiManuel($path, $importType, $from, $to, $logId = ''): void
    {
        $currentDir = $this->kernelProjectDir;
        shell_exec("php -dsafe_mode=Off $currentDir/../anlagen/$path/loadDataFromApi.php ".$from." ".$to." ".$importType." ".$logId);
    }
}
