<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\ImportService;
use App\Repository\AnlagenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pvp:startImport',
    description: '',
)]
class StartImportCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly ImportService $importService,
        private readonly AnlagenRepository $anlagenRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('methode', null, InputOption::VALUE_REQUIRED, 'The import type(manual, cron)' )
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Startdate Example 08-09-2024 for Import maual' )
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'Enddate Example 08-09-2024 for Import maual')
            ->addOption('anlid', null, InputOption::VALUE_REQUIRED, 'The id from the plant to import like 234')
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database name for data transfer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $methode = $input->getOption('methode');
        $from = $input->getOption('start');
        $to = $input->getOption('end');
        $plantId = $input->getOption('anlid');
        #$this->weatherService->calculateSunrise();

        if($methode == 'manuel'){
            $this->importManuel($from, $to, $plantId);
        }

        if($methode == 'cron'){
            $this->importCron();
        }

        return Command::SUCCESS;
    }

    private function importManuel(string $from, string $to,int $plantId){

        #echo  "$from,  $to, $plantId";

        date_default_timezone_set('UTC');
        $fromts = strtotime("$from 00:00:00") - 900;

        $tots = strtotime("$to 23:45:00");

        //get one Plant for Import manuell
        $anlage = $this->anlagenRepository->findOneByIdAndJoin($plantId);
        $step = 22*3600;
        $step2 = 24*3600;
        $i=1;
        for ($dayStamp = $fromts; $dayStamp < $tots; $dayStamp += $step2) {
            $from_new = $dayStamp;
            $to_new = $dayStamp + $step;

            if ($i > 1) {
                $from_new = $from_new - 7200;
            }

            if ($i == 1) {
                $to_new = $to_new + 7200;
            }

            $i++;
            $currentDay = date('d', $dayStamp);

            // Proof if date = today, if yes set $to to current DateTime
            if (date('Y', $to_new) == date('Y') && date('m', $to_new) == date('m') && $currentDay == date('d')) {
                $hour = date('H');
                $minute = date('i');
                $to_new = strtotime(date("Y-m-d $hour:$minute"), $to_new);
            }

            $minute = (int)date('i');

            while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {
                sleep(20);
                $minute = (int)date('i');
            }
            #echo "$from_new,  $to_new, $plantId";
            $this->importService->prepareForImport($anlage, $from_new, $to_new);

            sleep(1);
        }
    }

    private function importCron(){
        //get all Plants for Import via via Cron
        $anlagen = $this->anlagenRepository->findAllSymfonyImport();

        $time = time();
        $time -= $time % 900;
        $currentHour = (int)date('h');
        if ($currentHour >= 12) {
            $start = $time - (12 * 3600);
        } else {
            $start = $time - ($currentHour * 3600) + 900;
        }
        $start = $time - 4 * 3600;
        $end = $time;

        foreach ($anlagen as $anlage) {
            $this->importService->prepareForImport($anlage, $start, $end, '', true);
        }
    }
}
