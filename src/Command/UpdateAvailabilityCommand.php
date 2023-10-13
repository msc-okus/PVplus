<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AvailabilityByTicketService;
use App\Service\AvailabilityService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:updatePA',
    description: '',
)]
class UpdateAvailabilityCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly AvailabilityByTicketService $availabilityByTicket)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plantid')
            ->addOption('day', null, InputOption::VALUE_REQUIRED, 'Tag (day) im Format \'yyyy-mm-dd\' für den, die \'Verfügbarkeit\' berechnet werden soll.')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
        ;
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ergebniss = '';
        $io = new SymfonyStyle($input, $output);
        $day = $input->getOption('day');
        $anlageId = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');

        if ($day) {
            $day = strtotime((string) $day);
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
            $anlagen = $this->anlagenRepository->find($anlageId);
            $anzAnlagen = 1;
        } else {
            $io->comment("Berechne Verfügbarkeit: $from - $to | Alle Anlagen");
            $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]);
            $anzAnlagen = ($anlagen === null ? 0 : count($anlagen));
        }

        $fromStamp = strtotime((string) $from);
        $toStamp = strtotime((string) $to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            ++$counter;
        }

        $io->progressStart($anzAnlagen * $counter);
        foreach ($anlagen as $anlage) {
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                $from = date('Y-m-d 00:00', $stamp);
                if ($anlage->getAnlInputDaily() == 'Yes') {
                    $from = date('Y-m-d 00:00', $stamp - (24 * 3600)); // gestern, da Anlage heute keine Daten bekommt
                }

                $ergebniss = $this->availabilityByTicket->checkAvailability($anlage, $from, 0);
                $ergebniss = $this->availabilityByTicket->checkAvailability($anlage, $from, 2);
                $ergebniss = $this->availabilityByTicket->checkAvailability($anlage, $from, 1);
                $ergebniss = $this->availabilityByTicket->checkAvailability($anlage, $from, 3);

                $io->progressAdvance();
            }
            $io->comment($anlage->getAnlName());
            sleep(5);
        }
        $io->progressFinish();
        $io->success('Berechnung der Verfügbarkeit abgeschlossen!');

        return Command::SUCCESS;
    }
}
