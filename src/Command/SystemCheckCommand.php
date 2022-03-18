<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AlertSystemService;
use App\Service\ExportService;
use App\Service\MessageService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SystemCheckCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:systemCheck';

    private AlertSystemService $alertService;


    public function __construct(AlertSystemService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;

    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->alertService->checkSystem();
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
