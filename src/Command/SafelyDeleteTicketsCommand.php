<?php

namespace App\Command;

use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\TicketsGeneration\AlertSystemService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use App\Service\TicketsGeneration\InternalAlertSystemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'pvp:SafelyDeleteTickets',
    description: 'Command to delete tickets',
)]
class SafelyDeleteTicketsCommand extends Command
{
    public function __construct(
        private readonly TicketRepository       $ticketRepository,
        private readonly AnlagenRepository      $anlagenRepository,
        private readonly EntityManagerInterface $em,
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plantid = $input->getArgument('plantid');
        $optionFrom = $input->getOption('from');
        $optionTo = $input->getOption('to');
        #$anlage = $this->anlagenRepository->findIdLike([$plantid])[0];
        $anlage = $this->anlagenRepository->find($plantid);
        $tickets = $this->ticketRepository->findForSafeDelete($anlage, $optionFrom, $optionTo);
        foreach ($tickets as $ticket){
            $dates = $ticket->getDates();
            foreach ($dates as $date){
                $this->em->remove($date);
            }
            $this->em->remove($ticket);
        }
        $this->em->flush();
        return Command::SUCCESS;
    }
}

