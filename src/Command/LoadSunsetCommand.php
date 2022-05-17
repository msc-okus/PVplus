<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\WeatherServiceNew;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class LoadSunsetCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:loadSunset';
    private WeatherServiceNew $weatherService;

    public function __construct(WeatherServiceNew $weatherService){
        parent::__construct();
        $this->weatherService = $weatherService;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->weatherService->calculateSunrise();
        return Command::SUCCESS;
    }
}