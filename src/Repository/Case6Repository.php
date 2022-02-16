<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageCase6;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageCase6|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageCase6|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageCase6[]    findAll()
 * @method AnlageCase6[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Case6Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageCase6::class);
    }

    public function findCase6(Anlage $anlage, $inverter, $stamp)
    {
        $result = $this->createQueryBuilder('c6')
            ->andWhere('c6.anlage = :anlage and c6.inverter = :inverter')
            ->andWhere('c6.stampFrom < :stamp and c6.stampTo >= :stamp')
            ->setParameter('anlage', $anlage)
            ->setParameter('inverter', $inverter)
            ->setParameter('stamp', $stamp)
            ->select('count(c6.inverter)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        return ($result >= 1);
    }

    public function findAllCase6(Anlage $anlage, $from, $to)
    {
        $result = $this->createQueryBuilder('c6')
            ->andWhere('c6.anlage = :anlage')
            ->andWhere('c6.stampFrom BETWEEN :from AND :to')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult()
        ;
        return $result;
    }

    public function countCase6DayAnlage(Anlage $anlage, $day):int
    {
        $startMonth = date('Y-m-d 00:00', strtotime($day));
        $endMonth = date("Y-m-d 23:59", strtotime($day));
        return $this->createQueryBuilder('c6')
            ->andWhere('c6.anlage = :anlage')
            ->andWhere('c6.stampFrom >= :start and c6.stampTo <= :end')
            ->setParameter('anlage', $anlage)
            ->setParameter('start', $startMonth)
            ->setParameter('end', $endMonth)
            ->select('count(c6.inverter)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findAllAnlageDay(Anlage $anlage, $day)
    {
        $lastDayMonth = date('t', strtotime($day));
        $startMonth = date('Y-m-01 00:00', strtotime($day));
        $endMonth = date("Y-m-$lastDayMonth 23:59", strtotime($day));
        $result = $this->createQueryBuilder('c6')
            ->andWhere('c6.anlage = :anlage')
            ->andWhere('c6.stampFrom >= :start and c6.stampTo <= :end')
            ->setParameter('anlage', $anlage)
            ->setParameter('start', $startMonth)
            ->setParameter('end', $endMonth)
            ->getQuery()
            ->getResult()
            ;

        return $result;
    }
}
