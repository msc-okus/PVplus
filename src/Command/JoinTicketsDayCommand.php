<?php

namespace App\Command;

use App\Repository\AnlagenRepository;
use App\Service\AlertSystemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:joinTicketsDay',
    description: '',
)]
class JoinTicketsDayCommand extends Command
{

    private AlertSystemService $alertService;

    private AnlagenRepository $anlagenRepository;

    public function __construct(AnlagenRepository $anlagenRepository, AlertSystemService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
        $this->anlagenRepository = $anlagenRepository;
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Join the tickets for one day or an interval of days')
            ->addArgument('plantid')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to start')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to end')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plantid = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');

        $time = time();
        $time = $time - ($time % 900);
        if ($optionFrom) {
            $from = $optionFrom;
        } else {
            $from = date('Y-m-d H:i:00', $time);
        }
        if ($optionTo) {
            $to = $optionTo;
        } else {
            $to = date('Y-m-d H:i:00', $time);
        }

        if ($from <= $to) {
            $fromStamp = strtotime($from);
            $toStamp = strtotime($to);

            if (strtoupper($plantid) == 'ALL') {
                $io->comment("Generate Tickets: $from - $to | All Plants");
                $anlagen = $this->anlagenRepository->findBy(['anlHidePlant' => 'No', 'calcPR' => true]);
            } elseif (is_numeric($plantid)) {
                $io->comment("Generate Tickets: $from - $to | Plant ID: $plantid");
                $anlagen = $this->anlagenRepository->findIdLike([$plantid]);
            } else {
                $io->comment("Generate Tickets: $from - $to | Test Plants (93, 94, 96, 112, 113)");
                $anlagen = $this->anlagenRepository->findIdLike([93, 94, 96, 112, 113]);
            }

            $counter = (($toStamp - $fromStamp) / 86400) * count($anlagen);
            $io->progressStart($counter);
            $counter = ($counter * 4) - 1;

            foreach ($anlagen as $anlage) {

                while (((int) date('i') >= 26 && (int) date('i') < 35) || (int) date('i') >= 56 || (int) date('i') < 5) {
                    $io->comment('Wait...');
                    sleep(30);
                }

                for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 86400) {
                    $this->alertService->joinTicketsForTheDay($anlage, date('Y-m-d', $stamp));
                        $io->progressAdvance();

                    --$counter;
                }
                $io->comment($anlage->getAnlName());
            }
            $io->progressFinish();
            $io->success('Generating tickets finished');
        }

        return Command::SUCCESS;
    }
}
