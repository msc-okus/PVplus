<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\ExpectedService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:updateExpected',
    description: '',
)]
class UpdateExpectedDbsCommand extends Command
{
    use G4NTrait;

    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly ExpectedService $expected
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plantid', InputArgument::OPTIONAL, 'Anlagen ID für die, die Berechnung ausgeführt werden soll oder nichts, dann werden alle Anlagen berechnet')
            // ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
        ;
    }

    /**
     * @throws NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // $anlageId = $input->getOption('anlage');
        $anlageId = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');

        if ($optionFrom) {
            $from = $optionFrom;
        } else {
            $from = date('Y-m-d H:i:00', time() - (4 * 3600));
           # $from = date('Y-m-d 00:00:00', time());
        }
        if ($optionTo) {
            $to = $optionTo;
        } else {
            $to = date('Y-m-d H:i:00', time());
        }

        $io->comment("Update AC and DC expected: from $from to $to");

        if ($anlageId) {
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
        } else {
            $anlagen = $this->anlagenRepository->findUpdateExpected();
        }

        $fromStamp = strtotime((string) $from);
        $toStamp = strtotime((string) $to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            ++$counter;
        }

        foreach ($anlagen as $anlage) {
            $io->progressStart($counter);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                $from = date('Y-m-d 00:00', $stamp);
                $to = date('Y-m-d 23:59', $stamp);
                $io->progressAdvance();
                $output = $this->expected->storeExpectedToDatabase($anlage, $from, $to);
            }
            $io->comment($anlage->getAnlName().' - '.$anlage->getAnlId());
            sleep(5);
        }
        $io->progressFinish();
        $io->success('Berechnung der Soll Werte abgeschlossen!');

        return Command::SUCCESS;
    }
}
