<?php

namespace App\EventListener;

use App\Entity\Ticket;
use App\Entity\TicketDate;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: TicketDate::class)]
class TicketDateChangeNotifier
{
    public function __construct(
    )
    {
    }

    public function preUpdate(TicketDate $ticketDate, PreUpdateEventArgs $event): void
    {
        $em = $event->getObjectManager();
        if (sizeof($event->getEntityChangeSet()) > 0) {
            $ticket = $ticketDate->getTicket();
            $ticket->setUpdatedAt(new \DateTime('now'));
            #dump($event->getEntityChangeSet(), $em);
            #$em->persist($ticket);
        }

    }
}

# 395822