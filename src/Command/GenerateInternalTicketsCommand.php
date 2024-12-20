<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\TicketsGeneration\AlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use App\Service\TicketsGeneration\InternalAlertSystemService;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'pvp:generateInternalTickets',
    description: 'Command to generate internal tickets',
)]
class GenerateInternalTicketsCommand extends Command
{
    use G4NTrait;
    public function __construct(
        private readonly AnlagenRepository          $anlagenRepository,
        private readonly InternalAlertSystemService $alertService,
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Generate Internal Tickets')
            ->addArgument('plantid')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to start')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to end')
        ;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
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
                    $offsetServer = new DateTimeZone("Europe/Luxembourg");
                    $plantoffset = new DateTimeZone($anlage->getNearestTimezone());
                    $totalOffset = $plantoffset->getOffset(new DateTime("now")) - $offsetServer->getOffset(new DateTime("now"));
                    $this->alertService->checkSystem($anlage, date('Y-m-d H:i:00', $stamp + $totalOffset));
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
