<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\WeatherServiceNew;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'pvp:loadSunset',
    description: '',
)]
class LoadSunsetCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly WeatherServiceNew $weatherService
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
