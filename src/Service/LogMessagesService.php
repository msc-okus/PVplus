<?php

namespace App\Service;

use App\Entity\Anlage;
use App\Entity\LogMessages;
use App\Repository\LogMessagesRepository;
use Doctrine\ORM\EntityManagerInterface;

class LogMessagesService
{
    private EntityManagerInterface $em;

    private LogMessagesRepository $logMessagesRepo;

    public function __construct(EntityManagerInterface $em, LogMessagesRepository $logMessagesRepo)
    {
        $this->em = $em;
        $this->logMessagesRepo = $logMessagesRepo;
    }

    public function writeNewEntry(Anlage $anlage, string $function, string $job): int
    {
        $log = new LogMessages();
        $log
            ->setPlant($anlage->getAnlName().' - '.$anlage->getProjektNr())
            ->setFunction($function)
            ->setJob($job)
            ->setStartedAt(new \DateTimeImmutable())
            ->setState('waiting')
        ;
        $this->em->persist($log);
        $this->em->flush();

        return $log->getId();
    }

    public function updateEntry(int $id, string $state, ?int $progress = null)
    {
        $log = $this->logMessagesRepo->findOneBy(['id' => $id]);
        $log->setState($state);
        if ($progress) {
            $log->setProgress($progress);
        }
        if ($state == 'done') {
            $log->setFinishedAt(new \DateTimeImmutable());
        }
        $this->em->flush();
    }
}
