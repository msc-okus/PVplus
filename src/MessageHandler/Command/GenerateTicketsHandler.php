<?php
namespace App\MessageHandler\Command;

use App\Entity\Anlage;
use App\Message\Command\GenerateTickets;
use App\Repository\AnlagenRepository;
use App\Repository\TicketRepository;
use App\Service\LogMessagesService;
use App\Service\TicketsGeneration\AlertSystemV2Service;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateTicketsHandler
{
    public function __construct(
        private readonly AlertSystemv2Service $alertServiceV2,
        private readonly LogMessagesService $logMessages,
        private readonly AnlagenRepository $anlagenRepo,
        private readonly EntityManagerInterface $em,
        private readonly TicketRepository $ticketRepo,
    ){
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(GenerateTickets $generateTickets): void
    {
        /** @var $anlage Anlage */
        $anlage = $this->anlagenRepo->find($generateTickets->getAnlageId());
        $logId = $generateTickets->getlogId();

        $this->logMessages->updateEntry($logId, 'working');
        $timeCounter = 0;
        $timeRange = $generateTickets->getEndDate()->getTimestamp() - $generateTickets->getStartDate()->getTimestamp();
        $tickets = $this->ticketRepo->findForSafeDelete($anlage, $generateTickets->getStartDate()->format('Y-m-d'), $generateTickets->getEndDate()->format('Y-m-d'));
        foreach ($tickets as $ticket) {
            $dates = $ticket->getDates();
            foreach ($dates as $date) {
                $this->em->remove($date);
            }
            $this->em->remove($ticket);
        }
        $this->em->flush();
        for ($stamp = $generateTickets->getStartDate()->getTimestamp(); $stamp <= $generateTickets->getEndDate()->getTimestamp(); $stamp = $stamp + (24 * 3600)) {
            $this->logMessages->updateEntry($logId, 'working', ($timeCounter / $timeRange) * 100);
            $timeCounter += 24 * 3600;
            $this->alertServiceV2->generateTicketsInterval($anlage, date('Y-m-d H:i:00', $stamp), date('Y-m-d H:i:00', $stamp + (24 * 3600)));
            $this->alertServiceV2->checkExpected($anlage, date('Y-m-d H:i:00', $stamp));
        }
        $this->logMessages->updateEntry($logId, 'done', 100);
    }
}