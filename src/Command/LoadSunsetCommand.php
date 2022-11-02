<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\WeatherServiceNew;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadSunsetCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:loadSunset';

    public function __construct(
        private WeatherServiceNew $weatherService
    )
    {
        parent::__construct();
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
