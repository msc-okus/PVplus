<?php

namespace App\Command;

use App\Service\PvpDivisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'PvpDataToDivisionGrouped',
    description: 'Übertragung von allen DB',
)]
class PvpDataToDivisionGroupedCommand extends Command
{
    public function __construct(private readonly PvpDivisionService $tableService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {

        $this
            ->addOption('sm', null, InputOption::VALUE_REQUIRED, 'The initial month for data transfer')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The final month for data transfer')
            ->addOption('y', null, InputOption::VALUE_REQUIRED, 'The year for data transfer')
        ;
    }

    private function group(ProgressBar $progressBar,int $year, int $month){

        //It calculates the start date of the month and the end date of the month .
        $startDate = date('Y-m-01 00:00:00', strtotime("$year-$month-01"));
        $endDate = date('Y-m-t 23:59:59', strtotime("$year-$month-01"));

        // Indicate the start of the progress
        $progressBar->start();


        $this->tableService->transferData($startDate, $endDate);

        // Advance the progress bar
        $progressBar->advance();

        // Finish the progress
        $progressBar->finish();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startMonth = $input->getOption('sm');
        $endMonth = $input->getOption('em');
        $year = $input->getOption('y');



        $progressBar = new ProgressBar($output);

        // Set the number of steps in the progress
        $progressBar->setMaxSteps($endMonth - $startMonth + 1);


        for ($month = $startMonth; $month <= $endMonth; $month++) {

            $this->group($progressBar,$year,$month);

        }

        // Clear the progress bar
        $output->writeln('');
        $output->writeln('terminated');
        return Command::SUCCESS;
    }

    /* php bin/console PvpDataToDivisionGrouped --sm 7 --em 7 --y 2023 */
}
