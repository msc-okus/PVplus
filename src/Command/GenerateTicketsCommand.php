<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Service\AlertSystemService;
use App\Service\WeatherServiceNew;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
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
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to start')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'the date we want the generation to end')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $plantid    = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo   = $input->getOption('to');

        $time = time();
        $time = $time - ($time % 900);
        if ($optionFrom) {
            $from = $optionFrom;
        } else {
            $from = date("Y-m-d H:i:00", $time - (2 * 3600));
        }
        if ($optionTo) {
            $to = $optionTo;
        } else {
            $to = date("Y-m-d H:i:00", $time);
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
                $io->comment("Generate Tickets: $from - $to | Test Plants (93, 94, 111, 112, 113)");
                $anlagen = $this->anlagenRepository->findIdLike([93, 94, 111, 112, 113]);
            }

            $counter = (($toStamp - $fromStamp) / 3600 ) * count($anlagen);
            $io->progressStart($counter);
            $counter = ($counter * 4) - 1;

            foreach ($anlagen as $anlage) {
                for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 900){
                    while (((int)date('i') >= 28 && (int)date('i') < 33) || (int)date('i') >= 58 || (int)date('i') < 3) {
                        $io->comment("Wait...");
                        sleep(20);
                    }
                    $this->alertService->checkSystem($anlage, $from = date("Y-m-d H:i:00",$stamp));
                    if ($counter % 4 == 0) $io->progressAdvance();
                    $counter--;
                }
                $io->comment($anlage->getAnlName());
            }
            $io->progressFinish();
            $io->success('Generating tickets finished');
        }

        return Command::SUCCESS;
    }
}
