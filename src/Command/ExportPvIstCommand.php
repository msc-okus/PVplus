<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\ExportService;
use App\Service\PRCalulationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportPvIstCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:exportPvIst';

    private AnlagenRepository $anlagenRepository;
    private ExportService $exportService;

    public function __construct(AnlagenRepository $anlagenRepository, ExportService $exportService)
    {
        parent::__construct();
        $this->anlagenRepository = $anlagenRepository;
        $this->exportService = $exportService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Export der pv_ist Tabelle ')
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss          = '';
        $io                 = new SymfonyStyle($input, $output);
        $anlageId           = $input->getOption('anlage');
        $optionFrom         = $input->getOption('from');
        $optionTo           = $input->getOption('to');


        if ($optionFrom) {
            $from = $optionFrom . ' 00:00:00';
        } else {
            $from = date("Y-m-1 00:00:00", time());
        }
        if ($optionTo) {
            $to = $optionTo . ' 23:59:00';
        } else {
            $lastday = date('t', time());
            $to = date("Y-m-$lastday 23:59:00", time());
        }
        $startYear = (int)date('Y', strtotime($from));
        $endYear = (int)date('Y', strtotime($to));
        $startMonth = (int)date('m', strtotime($from));
        $endMonth = (int)date('m', strtotime($to));
        $anzMonth = self::g4nDateDiffMonth($from, $to);

        if ($anlageId) {
            $io->comment("Exportiere: $from - $to Anlage ID: $anlageId");
            $anlage = $this->anlagenRepository->findOneBy(['anlId' => $anlageId]);

            $io->progressStart($anzMonth);
            $month = $startMonth;
            $year = $startYear;
            while ($anzMonth > 0) {
                if ($month > 12) {
                    $startYear++;
                    $month = 1;
                }
                $from = "$year-$month-1";
                $to = "$year-$month-". date('t', strtotime($from));
                //dump("$from - $to");
                $ergebniss .= $this->exportService->getRawData($anlage, date_create($from), date_create($to));
                sleep(1);
                $io->progressAdvance();
                $io->comment($anlage->getAnlName());
                $month++;
                $anzMonth--;
            }

            $io->progressFinish();
            $io->success('Export abgeschlossen abgeschlossen!');
        } else {
            $io->error('Keine Anlage angegeben.');
        }

        return Command::SUCCESS;
    }
}
