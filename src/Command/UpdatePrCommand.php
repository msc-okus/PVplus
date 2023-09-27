<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\PRCalulationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:updatePr',
    description: '',
)]
class UpdatePrCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private PRCalulationService $prCalulation
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plantid', InputArgument::OPTIONAL, 'Anlagen ID für die, die Berechnung ausgeführt werden soll oder nichts, dann werden alle Anlagen berechnet')
            ->addOption('day', null, InputOption::VALUE_REQUIRED, 'Tag (day) im Format \'yyyy-mm-dd\' für den, der PR berechnet werden soll.')
           // ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
            ->addOption('lastMonth', 'lm', InputOption::VALUE_NONE, 'Berechne PR für letzten Monat (ausgehen vom aktuellen Datum).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);
        $day = $input->getOption('day');
        // $anlageId           = $input->getOption('anlage');
        $anlageId = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');
        $optionLastMonth = $input->getOption('lastMonth');

        if ($day) {
            $day = strtotime($day);
            $from = date('Y-m-d 00:00', $day);
            $to = date('Y-m-d 23:50', $day);
        } elseif ($optionLastMonth) {
            $month = date('m');
            if ($month == 1) {
                // $month = Januar => $month auf Dezember und $year auf letztes Jahr
                $month = 12;
                $year = date('Y') - 1;
            } else {
                // $month != Januar => $month auf letzten Monat, $year auf aktuelles Jahr
                --$month;
                $year = date('Y');
            }
            $lastDayOfMonth = date('t', strtotime($year.'-'.$month.'-01'));
            $from = "$year-$month-01 00:00";
            $to = "$year-$month-$lastDayOfMonth 23:59";
        } else {
            if ($optionFrom) {
                $from = $optionFrom.' 00:00:00';
            } else {
                $from = date('Y-m-d 00:00:00', time() - (48 * 3600));
            }
            if ($optionTo) {
                $to = $optionTo.' 23:59:00';
            } else {
                $to = date('Y-m-d 23:59:00', time() - (24 * 3600));
            }
        }

        if ($anlageId) {
            $io->comment("Berechne PR: $from - $to Anlage ID: $anlageId");
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
        } else {
            $io->comment("Berechne PR: $from - $to Alle Anlagen");
            $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]); // , 'anlView' => 'Yes']);
        }

        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            ++$counter;
        }

        $io->progressStart(count($anlagen) * $counter);
        foreach ($anlagen as $anlage) {
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                $from = date('Y-m-d', $stamp);
                $ergebniss .= $this->prCalulation->calcPRAll($anlage, $from);
                $io->progressAdvance();
            }
            $io->comment($anlage->getAnlName());
            sleep(5);
        }
        $io->progressFinish();
        $io->success('Berechnung des PR abgeschlossen!');

        return Command::SUCCESS;
    }
}
