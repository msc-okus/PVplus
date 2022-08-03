<?php

namespace App\Repository;

use App\Entity\TicketDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketDate>
 *
 * @method TicketDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TicketDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TicketDate[]    findAll()
 * @method TicketDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketDate::class);
    }

    public function add(TicketDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TicketDate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return TicketDate[] Returns an array of TicketDate objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    public function findOneById($id): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    public function findOneByBeginTicket($begin, $ticket): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.begin = :begin')
            ->andWhere('t.ticket = :ticket')
            ->setParameter('begin', $begin)
            ->setParameter('ticket', $ticket)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    public function findOneByEndTicket($end, $ticket): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.ticket = :ticket')
            ->setParameter('end', $end)
            ->setParameter('ticket', $ticket)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    public function countByIntervalErrorPlant($begin, $end, $error, $anlage){
        return $this->createQueryBuilder('t')
            ->addSelect('sum(t.Intervals)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.errorType = :error')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('error', $error)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
            ;
    }
    public function countTicketsByIntervalErrorPlant($begin, $end, $error, $anlage){
        return $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.errorType = :error')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('error', $error)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
            ;
    }
    public function countByIntervalNullPlant($begin, $end, $anlage){
        return $this->createQueryBuilder('t')
            ->addSelect('sum(t.Intervals)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.dataGapEvaluation is NULL ')
            ->andWhere('t.errorType = 20')
            ->andWhere('t.alertType = 10')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
            ;
    }
}
