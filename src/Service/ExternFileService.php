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
        private readonly PVSystDatenRepository $pvSystRepo,
        private readonly AnlagenRepository $anlagenRepository,
        private readonly PRRepository $PRRepository,
        private readonly AnlageAvailabilityRepository $anlageAvailabilityRepo,
        private readonly FunctionsService $functions,
        private readonly EntityManagerInterface $em,
        private readonly AvailabilityService $availabilityService,
        private $kernelProjectDir
    )
    { }

    public function callFileServiceAPI(Anlage|int $anlage, string $day): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);
        }
        $timeStamp = strtotime($day);
        $fromDate = date('Y-m-d', $timeStamp);
        $functionlist[] = ['anlagen_id' => '105','path' => './anlagen/InnaxNL/', 'script' => 'loadData.php' ];

        foreach ($functionlist as $item => $value){
            if ($value['anlagen_id'] === $anlage->getAnlagenId()){
                $currentDir = $this->kernelProjectDir;
                exec("php -dsafe_mode=Off $currentDir/../anlagen/InnaxNL/loadData.php $fromDate > /dev/null &");
                $output = "Success";
              } else {
                $output = "Nothing to do";
            }
        }

        return $output;
    }
    public function callFileServiceINAX(Anlage|int $anlage, string $day): string
    {
        if (is_int($anlage)) {
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlage]);
        }
        $timeStamp = strtotime($day);
        $fromDate = date('Y-m-d', $timeStamp);
        $functionlist[] = ['anlagen_id' => '105','path' => './anlagen/InnaxNL/', 'script' => 'loadData.php' ];

        $filesystem = new Filesystem();
        try {
            $realpath = $filesystem->tempnam('/tmp', '' );
            $jsondata = json_encode($functionlist);
            file_put_contents($realpath, $jsondata);
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at " . $exception->getPath();
        }
        $output = "Error";
        foreach ($functionlist as $value) {
            if ($value['anlagen_id'] === $anlage->getAnlagenId()){
                $currentDir = $this->kernelProjectDir;
                exec("php -dsafe_mode=Off $currentDir/../anlagen/InnaxNL/loadData.php $fromDate > /dev/null &");
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
        exec("php -dsafe_mode=Off ../../anlagen/$path/loadDataFromApi.php ".$from." ".$to." ".$importType." ".$logId);
    }
}
