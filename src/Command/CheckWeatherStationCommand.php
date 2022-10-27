<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AlertSystemService;
use App\Service\AlertSystemWeatherService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckWeatherStationCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:weatherCheck';

    private AlertSystemWeatherService $alertService;

    private AnlagenRepository $anlRepo;

    public function __construct(AlertSystemWeatherService $alertService, AnlagenRepository $anlRepo)
    {
        parent::__construct();
        $this->alertService = $alertService;
        $this->anlRepo = $anlRepo;
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
        $anlagen = $this->anlRepo->findIdLike([93, 94, 96, 112, 113]);
        foreach ($anlagen as $anlage) {
            $this->alertService->checkWeatherStation($anlage);
        }
        return Command::SUCCESS;
    }
}
