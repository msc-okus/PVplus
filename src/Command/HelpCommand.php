<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\DummySollService;
use App\Service\ReportEpcService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class HelpCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:help';

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private ReportEpcService $reportEpc
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Hilfs Command zu testen.')
        ;
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $anlage = $this->anlagenRepository->find(95);
        $reportDate = new \DateTime("2022-10-31");

        $io->comment("Starte Hilfs Command: ");

        $ergebniss .= $this->reportEpc->createEpcReport($anlage, $reportDate);

        $io->success("Fertig");

        return Command::SUCCESS;
    }
}
