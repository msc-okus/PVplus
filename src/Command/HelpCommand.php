<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
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
        private ReportEpcService $reportEpc,
        private AssetManagementService $assetManagement
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Hilfs Command zum testen.')
        ;
    }

    /**
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $anlage = $this->anlagenRepository->find(95); // 183 = REGebeng
        $reportDate = new \DateTime("2022-12-31");
        $reportMonth = 12;
        $reportYear = 2022;

        $io->comment("Starte Hilfs Command: AM Report ".$anlage->getAnlName());

        #$ergebniss .= $this->reportEpc->createEpcReport($anlage, $reportDate);
        $ergebniss .= $this->assetManagement->createAmReport($anlage, $reportMonth, $reportYear);

        $io->success("Fertig");

        return Command::SUCCESS;
    }
}
