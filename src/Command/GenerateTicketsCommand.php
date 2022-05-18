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
        $ergebniss          = '';
        $io                 = new SymfonyStyle($input, $output);
        $anlageId           = $input->getOption('anlage');
        $optionFrom         = $input->getOption('from');
        $optionTo           = $input->getOption('to');
        $this->alertService->generateTicketsInterval($optionFrom, $optionTo, $anlageId);
        return Command::SUCCESS;
    }
}
