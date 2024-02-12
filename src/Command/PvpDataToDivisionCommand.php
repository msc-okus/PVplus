<?php

namespace App\Command;

use App\Service\PvpDivisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'PvpDataToDivision',
    description: 'Add a short description for your command',
)]
class PvpDataToDivisionCommand extends Command
{
    public function __construct(private readonly PvpDivisionService $tableService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    private function group(ProgressBar $progressBar){
        $month=6;
        $startDate = date('Y-m-01 00:00:00', strtotime("2023-$month-01"));
        $endDate = date('Y-m-t 23:59:59', strtotime("2023-$month-01"));

        // Indicate the start of the progress
        $progressBar->start();


        $this->tableService->transferData($startDate, $endDate);

        // Advance the progress bar
        $progressBar->advance();

        // Finish the progress
        $progressBar->finish();
    }
    private function solo(ProgressBar $progressBar,int $year, int $month,string $dbname){


        //It calculates the start date of the month and the end date of the month .
        $startDate = date('Y-m-01 00:00:00', strtotime("$year-$month-01"));
        $endDate = date('Y-m-t 23:59:59', strtotime("$year-$month-01"));

        // Indicate the start of the progress
        $progressBar->start();
        $this->tableService->transferDataOne($startDate, $endDate,$dbname);
        // Advance the progress bar
        $progressBar->advance();

        // Finish the progress
        $progressBar->finish();

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $progressBar = new ProgressBar($output);

        // Set the number of steps in the progress
        $progressBar->setMaxSteps(10);

        // Call your methods with the progress bar
        //$this->group($progressBar);
        // or
        for ($month = 1; $month <= 1; $month++) {


        //    $this->solo($progressBar,2024,$month , 'db__pv_dcist_CX104');
        }
        // Indicate the start of the progress
        $progressBar->start();

        // Advance the progress bar
        $progressBar->advance();

        // Finish the progress
        $progressBar->finish();
        // Clear the progress bar
        $output->writeln('');
        $output->writeln('terminated');
        return Command::SUCCESS;
    }

    // php bin/console PvpDataToDivision
}
