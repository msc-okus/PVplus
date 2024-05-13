<?php

namespace App\EventListener;

use App\Entity\Ticket;
use App\Entity\TicketDate;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Ticket::class)]
class TicketChangeNotifier
{
    public function __construct(
    )
    {
    }

    public function preUpdate(Ticket $ticket, PreUpdateEventArgs $event): void
    {

            #$ticket->setUpdatedAt(new \DateTime('now'));
            dump($ticket->getDates());

    }
}


# 395822