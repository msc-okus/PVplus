<?php

namespace App\Command;

use App\Repository\AnlagenRepository;
use App\Service\TicketsGeneration\AlertSystemWeatherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:GenerateWeatherTicket',
    description: 'Add a short description for your command',
)]
class GenerateWeatherTicketCommand extends Command
{
    public function __construct(
        private AnlagenRepository $anlagenRepository,
        private AlertSystemWeatherService $alertService,
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Generate weather Tickets')
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
            if (is_numeric($plantid)) {
                $io->comment("Generate Tickets: $from - $to | Plant ID: $plantid");
                $anlagen = $this->anlagenRepository->findIdLike([$plantid]);
            } else {
                $io->comment("Generate Tickets: $from - $to | All Plants");
                $anlagen = $this->anlagenRepository->findInternalAlertSystemActive(true);
            }
            $counter = (($toStamp - $fromStamp) / 3600) * count($anlagen);
            $io->progressStart($counter);
            $counter = ($counter * 4) - 1;
            foreach ($anlagen as $anlage) {
                while (((int) date('i') >= 26 && (int) date('i') < 35) || (int) date('i') >= 56 || (int) date('i') < 5) {
                    $io->comment('Wait...');
                    sleep(30);
                }
                for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900) {
                    $this->alertService->checkWeatherStation($anlage, date('Y-m-d H:i:00', $stamp));
                    if ($counter % 4 == 0) {
                        $io->progressAdvance();
                    }
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
