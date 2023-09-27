<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\CheckSystemStatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:updateSystemStatus',
    description: '',
)]
class UpdateSystemStatusCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private CheckSystemStatusService $checkSystemStatus
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment('System Status aktualisieren: Alle Anlagen');
        $ergebniss = $this->checkSystemStatus->checkSystemStatus();
        $io->success('Berechnung des System Status abgeschlossen!');

        return Command::SUCCESS;
    }
}
