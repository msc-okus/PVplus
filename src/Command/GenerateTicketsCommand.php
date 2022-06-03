<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AlertSystemService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class GenerateTicketsCommand extends Command
{
    use G4NTrait;

    protected static $defaultName = 'pvp:GenerateTickets';

    private EntityManagerInterface $em;
    private AlertSystemService $alertService;
    private AnlagenRepository $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository, AlertSystemService $alertService, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->alertService = $alertService;
        $this->em= $em;
        $this->anlagenRepository = $anlagenRepository;
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Generate Tickets')
            ->addArgument('plantid')
            #->addOption('anlage', 'a', InputOption::VALUE_REQUIRED, 'The plant we want to generate the tickets from')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to start')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to end')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        #$anlageId      = $input->getOption('anlage');
        $anlageId   = $input->getArgument('plantid');
        $from       = $input->getOption('from');
        $optionTo   = $input->getOption('to');
        $io->comment("Generate Tickets: from $from to $optionTo");
        $minute = (int)date('i');

        while (($minute >= 28 && $minute < 33) || $minute >= 58 || $minute < 3) {
            echo ".";
            sleep(20);
            $minute = (int)date('i');
        }

        if ($from <= $optionTo) {
            $to = G4NTrait::timeAjustment($optionTo, 24);
            $from .= " 00:00:00";
            $fromStamp = strtotime($from);
            $toStamp = strtotime($to);

            if ($anlageId) {
                $io->comment("Generate Tickets: $from - $to | Plant ID: $anlageId");
                $anlagen = $this->anlagenRepository->findIdLike([$anlageId]);
            } else {
                $io->comment("Generate Tickets: $from - $to | All Plants");
                $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]);
            }

            $counter = ($toStamp-$fromStamp) / 900;

            $io->progressStart($counter);
            foreach ($anlagen as $anlage) {
                while ($from <= $to) {//sleep
                    $this->alertService->checkSystem($anlage, $from);
                    $from = G4NTrait::timeAjustment($from, 0.25);
                    $io->progressAdvance();
                    usleep(1000);
                }

                $io->comment($anlage->getAnlName());
            }
            $io->progressFinish();
            $io->success('Generating tickets finished');
        }

        return Command::SUCCESS;
    }
}
