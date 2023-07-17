<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AssetManagementService;
use App\Service\Reports\ReportEpcService;
use Doctrine\Instantiator\Exception\ExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);

        $anlage = $this->anlagenRepository->find(104); //  = Saran

        $reportMonth = 05;
        $reportYear = 2023;

        $io->comment("Starte Hilfs Command: AM Report ".$anlage->getAnlName());
        #$ergebniss .= $this->reportEpc->createEpcReport($anlage, $reportDate);
         $this->assetManagement->createAmReport($anlage, $reportMonth, $reportYear);

        $io->success("Fertig");

        return Command::SUCCESS;
    }
}
