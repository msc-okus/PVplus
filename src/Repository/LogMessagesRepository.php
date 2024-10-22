<?php

namespace App\Repository;

use App\Entity\LogMessages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method LogMessages|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogMessages|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogMessages[]    findAll()
 * @method LogMessages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogMessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, LogMessages::class);
    }

    public function add(LogMessages $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(LogMessages $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findUsefull($uid)
    {
        if ($this->security->isGranted('ROLE_G4N')) {
            $q = $this->createQueryBuilder('log')
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600 * 4))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600 * 4))
                ->orderBy('log.startedAt', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $q = $this->createQueryBuilder('log')
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->andWhere("log.userId = $uid")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600))
                ->orderBy('log.startedAt', 'DESC')
                ->getQuery()
                ->getResult();
        }
        return $q;
    }

    public function findSmallList($uid)
    {
        if ($this->security->isGranted('ROLE_G4N')) {
            $q = $this->createQueryBuilder('log')
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600 * 4))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600 * 4))
                ->orderBy('log.startedAt', 'DESC')
                ->setMaxResults(4)
                ->getQuery()
                ->getResult();
        } else {
            $q = $this->createQueryBuilder('log')
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->andWhere("log.userId = $uid")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600))
                ->orderBy('log.startedAt', 'DESC')
                ->setMaxResults(4)
                ->getQuery()
                ->getResult();
        }

        return $q;
    }

    public function getStatusMessages($uid)
    {
        if ($this->security->isGranted('ROLE_G4N')) {
            $q = $this->createQueryBuilder('log')
                ->andWhere("log.state = 'done' AND log.isSeen = '0' AND log.progress = '100'")
                ->orderBy('log.finishedAt')
                ->setMaxResults(1);
        } else {
            $q = $this->createQueryBuilder('log')
                ->andWhere("log.state = 'done' AND log.isSeen = '0' AND log.progress = '100'")
                ->andWhere("log.userId = $uid")
                ->orderBy('log.finishedAt')
                ->setMaxResults(1);
        }
        try {
            return $q->getQuery()->getOneOrNullResult();
        }
        catch (NonUniqueResultException $e) {
            return 'noMessage';
        }

    }

    public function setStatusMessagesIsSeen($id)
    {
        $q = $this->createQueryBuilder('log')
            ->update()
            ->set('log.isSeen', 1)
            ->where("log.id = $id");
        try {
            return $q->getQuery()->execute();
        }
        catch(\Doctrine\ORM\NoResultException $e) {
            return 'noMessage';
        }


    }
}
