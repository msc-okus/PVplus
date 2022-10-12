<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\AvailabilityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateAvailabilityCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:updatePA';

    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private AvailabilityService $availability,
        private AvailabilityByTicketService $availabilityByTicket)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Berechnung der Verfügbarkeit (availability) ')
            ->addArgument('plantid')
            ->addOption('day', null, InputOption::VALUE_REQUIRED, 'Tag (day) im Format \'yyyy-mm-dd\' für den, die \'Verfügbarkeit\' berechnet werden soll.')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);
        $day = $input->getOption('day');
        $anlageId = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');

        if ($day) {
            $day = strtotime($day);
            $from = date('Y-m-d 04:00', $day);
            $to = date('Y-m-d 22:00', $day);
        } else {
            if ($optionFrom) {
                $from = $optionFrom;
            } else {
                $from = date('Y-m-d H:i:00', time() - (4 * 3600));
            }
            if ($optionTo) {
                $to = $optionTo;
            } else {
                $to = date('Y-m-d H:i:00', time());
            }
        }

        if ($anlageId) {
            $io->comment("Berechne Verfügbarkeit: $from - $to | Anlage ID: $anlageId");
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
        } else {
            $io->comment("Berechne Verfügbarkeit: $from - $to | Alle Anlagen");
            $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]);
        }

        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            ++$counter;
        }

        $io->progressStart(count($anlagen) * $counter);
        foreach ($anlagen as $anlage) {
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                $from = date('Y-m-d 00:00', $stamp);
                if ($anlage->getAnlInputDaily() == 'Yes') {
                    $from = ($from - (24 * 3600)); // gestern, da Anlage heute keine Daten bekommt
                }
                if ($anlage->getAnlId() == 112 || $anlage->getAnlId() == 113) {
                    $ergebniss = $this->availabilityByTicket->checkAvailability($anlage, strtotime($from), 1);
                } else {
                    $ergebniss = $this->availability->checkAvailability($anlage, strtotime($from));
                    if ($anlage->getShowAvailabilitySecond()) {
                        $ergebniss .= $this->availability->checkAvailability($anlage, strtotime($from), true); // Second
                    }
                }

                $io->progressAdvance();
            }
            sleep(2);
        }
        $io->progressFinish();
        $io->success('Berechnung der Verfügbarkeit abgeschlossen!');

        return Command::SUCCESS;
    }
}
