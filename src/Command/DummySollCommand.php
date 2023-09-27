<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Service\DummySollService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pvp:dummysoll', description: 'Lege Datensätze in DummySoll Datenbanken an.')]
class DummySollCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private DummySollService $dummySoll
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('day', InputArgument::OPTIONAL, 'Tag (day) im Format \'yyyy-mm-dd\' für den, \'DummySoll\' berechnet werden soll.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);
        $day = $input->getArgument('day');

        if ($day) {
            $from = strtotime($day);
        } else {
            $from = null;
        }

        $io->comment("Berechne DummySoll: $from - Alle Anlagen");

        $ergebniss .= $this->dummySoll->createDummySoll($from);

        $io->success($ergebniss);

        return Command::SUCCESS;
    }
}
