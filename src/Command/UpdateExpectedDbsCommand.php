<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\ExpectedService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateExpectedDbsCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:updateExpected';

    private AnlagenRepository $anlagenRepository;
    private ExpectedService $expected;

    public function __construct(AnlagenRepository $anlagenRepository, ExpectedService $expected)
    {
        parent::__construct();
        $this->anlagenRepository = $anlagenRepository;
        $this->expected = $expected;
    }


    protected function configure()
    {
        $this
            ->setDescription('Erzeugt die SOll Daten für AC und DC')
            ->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'Anlagen ID für die, die Berechnung ausgeführt werden soll')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Datum ab dem berechnet werden soll')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Datum bis zu dem berechnet werden soll')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $anlageId = $input->getOption('anlage');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');


        if ($optionFrom) {
            $from = $optionFrom;
        } else {
            $from = date("Y-m-d H:i:00", time() - (2 * 3600));
        }
        if ($optionTo) {
            $to = $optionTo;
        } else {
            $to = date("Y-m-d H:i:00", time());
        }


        //$from       = '2020-08-04 04:00';
        //$to         = '2020-08-06 22:00';

        $io->comment("Update AC and DC expected: from $from to $to");

        if ($anlageId) {
            $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
        } else {
            $anlagen = $this->anlagenRepository->findUpdateExpected();
        }

        $fromStamp = strtotime($from);
        $toStamp = strtotime($to);
        $counter = 0;
        for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
            $counter++;
        }
        global $conn;
        $conn = self::connectToDatabase();
        foreach ($anlagen as $anlage) {
            $io->progressStart($counter);
            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp = $stamp + (24 * 3600)) {
                $from = date('Y-m-d 00:00',$stamp);
                $to = date('Y-m-d 23:59',$stamp);
                $io->progressAdvance();
                $output = $this->expected->storeExpectedToDatabase($anlage, $from, $to);
            }
            $io->comment($anlage->getAnlName() . " - " . $anlage->getAnlId());
        }
        $io->progressFinish();
        $io->success('Berechnung der Soll Werte abgeschlossen!');
        $conn->close();

        return Command::SUCCESS;
    }
}
