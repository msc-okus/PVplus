<?php

namespace App\Command;

use App\Helper\G4NTrait;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\TicketsGeneration\AlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'pvp:GenerateExpectedTickets',
    description: 'We use this command to generate tickets for expected/actual relation.',
)]
class GenerateExpectedTicketsCommand extends Command
{
    use G4NTrait;


    public function __construct(
        private readonly AnlagenRepository $anlagenRepository,
        private readonly AlertSystemService $alertService,
        private readonly AlertSystemv2Service $alertServiceV2,
        private readonly EntityManagerInterface $em,
        private readonly TicketRepository $ticketRepo,
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
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

        if (is_numeric($plantid)) {
            $io->comment("Generate Tickets | Plant ID: $plantid");
            $anlagen = $this->anlagenRepository->findIdLike([$plantid]);
        } else {
            $io->comment("Generate Tickets | All Plants");
            $anlagen = $this->anlagenRepository->findExpectedAlertSystemActive(true, true);
        }

        foreach ($anlagen as $anlage) {
            $tickets = $this->ticketRepo->findForSafeDelete($anlage, $optionFrom, $optionTo, 60);
            foreach ($tickets as $ticket) {
                $notifications = $ticket->getNotificationInfos();
                foreach ($notifications as $notification) {
                    $files = $notification->getAttachedMedia();
                    foreach ($files as $file){
                        $this->em->remove($file);
                    }
                    $works = $notification->getNotificationWorks();
                    foreach ($works as $work){
                        $this->em->remove($work);
                    }
                    $this->em->remove($notification);
                }
                $dates = $ticket->getDates();
                foreach ($dates as $date) {
                    $this->em->remove($date);
                }
                $this->em->remove($ticket);
            }
            $this->em->flush();
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

            $fromStamp = strtotime((string)$from);
            $toStamp = strtotime((string)$to);

            $counter = (($toStamp - $fromStamp) / 3600) * (is_countable($anlagen) ? count($anlagen) : 0);
            $io->progressStart($counter);
            $counter = ($counter * 4) - 1;

            for ($stamp = $fromStamp; $stamp <= $toStamp; $stamp += 86400) {

                $this->alertServiceV2->generateTicketsExpectedInterval($anlage, date('Y-m-d H:i:00', $stamp));
                if ($counter % 4 == 0) {
                    $io->progressAdvance();
                }
                --$counter;
            }
            $io->comment($anlage->getAnlName());
        }

        $io->progressFinish();
        $io->success('Generating tickets finished');
        return Command::SUCCESS;
    }
}
