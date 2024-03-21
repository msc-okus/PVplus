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
    name: 'PvpDataToDivision',
    description: 'Übertragung von einzelnen DB',
)]
class PvpDataToDivisionCommand extends Command
{
    public function __construct(private readonly PvpDivisionService $tableService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {

        $this
            ->addOption('sm', null, InputOption::VALUE_REQUIRED, 'The initial month for data transfer' )
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The final month for data transfer')
            ->addOption('y', null, InputOption::VALUE_REQUIRED, 'The year for data transfer')
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database name for data transfer')
        ;
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
        $startMonth = $input->getOption('sm');
        $endMonth = $input->getOption('em');
        $year = $input->getOption('y');
        $dbname = $input->getOption('db');
        $db='db__pv_dcist_'.$dbname;


        $progressBar = new ProgressBar($output);

        // Set the number of steps in the progress
        $progressBar->setMaxSteps($endMonth - $startMonth + 1);

        for ($month = $startMonth; $month <= $endMonth; $month++) {

           $this->solo($progressBar,$year,$month , $db);

        }

        // Clear the progress bar
        $output->writeln('');
        $output->writeln('terminated');
        return Command::SUCCESS;
    }

    /* php bin/console PvpDataToDivision --sm 7 --em 7 --y 2023 --db CX217 */
}
