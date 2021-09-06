<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\CheckSystemStatusService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateSystemStatusCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:updateSystemStatus';

    private $checkSystemStatus;

    public function __construct(CheckSystemStatusService $checkSystemStatus)
    {
        parent::__construct();
        $this->checkSystemStatus = $checkSystemStatus;
    }

    protected function configure()
    {
        $this->setDescription('Aktualisierung des System Status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment("System Status aktualisieren: Alle Anlagen");
        $ergebniss = $this->checkSystemStatus->checkSystemStatus();
        $io->success('Berechnung des System Status abgeschlossen!');

        return Command::SUCCESS;
    }
}
