<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\AlertSystemService;
use App\Service\WeatherServiceNew;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class GenerateTicketsCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:GenerateTickets';

    private AlertSystemService $alertService;


    public function __construct(AlertSystemService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;

    }
    protected function configure(): void
    {
        $this
            ->setDescription('Generate Tickets')
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'The plant we want to generate the tickets from')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to start')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to end')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io                 = new SymfonyStyle($input, $output);
        $anlId           = $input->getOption('anlage');
        $from         = $input->getOption('from');
        $optionTo           = $input->getOption('to');
        $io->comment("Generate Tickets: from $from to $optionTo");

        if ($from <= $optionTo) {
            $to = G4NTrait::timeAjustment($optionTo, 24);
            $from = $from." 00:00:00";
            $fromStamp = strtotime($from);
            $toStamp = strtotime($to);

            $counter = ($toStamp-$fromStamp)/800;

            $io->progressStart($counter);
            while($from <= $to){//sleep
                $io->progressAdvance();
                $this->alertService->checkSystem($from, $anlId);
                $from = G4NTrait::timeAjustment($from, 0.25);
            }
            $io->progressFinish();
        }
        return Command::SUCCESS;
    }
}
