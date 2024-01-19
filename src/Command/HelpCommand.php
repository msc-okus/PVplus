<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\ExportService;
use App\Service\Reports\ReportEpcService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:help',
    description: '',
)]
class HelpCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly ReportEpcService $reportEpc,
        private readonly AssetManagementService $assetManagement,
        private readonly ExportService $exportService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plantid', InputArgument::REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll.')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
            ->addOption('month', null, InputOption::VALUE_REQUIRED, 'Monat für den berechnet werden soll. Wenn kein Jahr angegeben, dann aktuelles Jahr')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Jahr für das berechnet werden soll. Wenn kein Monat angegeben, dann aktueller Monat')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $anlageId = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');
        $optionMonth = $input->getOption('month');
        $optionYear = $input->getOption('year');

        $anlage = $this->anlagenRepository->find($anlageId);

        if ($optionMonth && !$optionYear) {
            $optionYear = date('Y');
        } elseif ($optionYear && !$optionMonth) {
            $optionMonth = date('m');
        } elseif (!$optionMonth && !$optionYear) {
            $optionYear = date('Y');
            $optionMonth = date('m');
        }

        $reportMonth = $optionMonth;
        $reportYear = $optionYear;
        $from = date_create("$reportYear-$reportMonth-01 00:00");
        $lastDayOfMonth = $from->format('t');
        $to = date_create("$reportYear-$reportMonth-$lastDayOfMonth 23:55");

        $io->comment("Starte Hilfs Command: Export ".$anlage->getAnlName());
        #$ergebniss .= $this->reportEpc->createEpcReport($anlage, $reportDate);
        #$this->assetManagement->createAmReport($anlage, $reportMonth, $reportYear);
        $output = $this->exportService->gewichtetBavelseValuesExport($anlage, $from, $to);

        $io->success("Fertig");

        return Command::SUCCESS;
    }
}

