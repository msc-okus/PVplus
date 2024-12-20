<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\PRCalulationService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:UpdatePlantsWithDailyInput',
    description: '',
)]
class UpdatePlantsWithDailyInputCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly PRCalulationService $prCalulation,
        private readonly AvailabilityByTicketService $availability)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('day', InputArgument::OPTIONAL, 'Tag (day) im Format \'yyyy-mm-dd\' für den, der \'AC/DC Expected\' berechnet werden soll.')
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll')
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws InvalidArgumentException
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);
        $day = $input->getArgument('day');
        $anlageId = $input->getOption('anlage');

        if ($day) {
            $day = strtotime((string) $day);
            $from = date('Y-m-d', $day);
        } else {
            $from = date('Y-m-d', time() - 86400);
        }

        if ($anlageId) {
            $io->comment("Berechne PR und Verfügbarkeit: $from - Anlage ID: $anlageId");
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
        } else {
            $io->comment("Berechne PR und Verfügbarkeit: $from - Alle Anlagen");
            $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'anlView' => 'Yes', 'anlInputDaily' => 'Yes']);
        }

        $io->progressStart(count($anlagen));
        foreach ($anlagen as $anlage) {
            $this->prCalulation->calcPRAll($anlage, $from);
            $this->availability->checkAvailability($anlage, strtotime($from));
            $io->progressAdvance();
        }
        $io->progressFinish();
        $io->success('Berechnung des PR abgeschlossen!');

        return Command::SUCCESS;
    }
}
