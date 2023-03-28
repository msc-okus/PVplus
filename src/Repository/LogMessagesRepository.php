<?php

namespace App\Repository;

use App\Entity\LogMessages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method LogMessages|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogMessages|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogMessages[]    findAll()
 * @method LogMessages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogMessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private Security $security)
    {
        parent::__construct($registry, LogMessages::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(LogMessages $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(LogMessages $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findUsefull()
    {
        return $this->createQueryBuilder('log')
            ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
            ->setParameter('end', date('Y-m-d H:i:s', time() - 3600 * 6))
            ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600 * 48))
            ->orderBy('log.startedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSmallList()
    {
        if ($this->security->isGranted('ROLE_G4N')) {
            $q = $this->createQueryBuilder('log')
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600 * 1))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600 * 1))
                ->orderBy('log.startedAt', 'DESC')
                ->setMaxResults(4)
                ->getQuery()
                ->getResult();
        } else {
            $q = $this->createQueryBuilder('log')
                ->andWhere("log.function LIKE 'create AM Report%'")
                ->andWhere("(log.state = 'done' AND log.startedAt >= :end) or (log.state != 'done' and  log.startedAt >= :lastend)")
                ->setParameter('end', date('Y-m-d H:i:s', time() - 3600 * 1))
                ->setParameter('lastend', date('Y-m-d H:i:s', time() - 3600 * 1))
                ->orderBy('log.startedAt', 'DESC')
                ->setMaxResults(4)
                ->getQuery()
                ->getResult();
        }

        return $q;
    }

}
