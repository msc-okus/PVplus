<?php

namespace App\Command;



use App\Service\PvpDataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PvpDataCommand extends Command
{
    protected static $defaultName = 'app:pvp_data';



    public function __construct(private readonly PvpDataService $tableService)
    {

        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       $x='2023-01-01 00:00:00';
       $y='2023-01-31 23:59:59';
      // $y='2023-05-31 23:59:59';


       $this->tableService->transferData($x, $y);
        return Command::SUCCESS;
    }

}
