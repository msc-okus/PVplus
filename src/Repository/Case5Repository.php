<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageCase5;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageCase5|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageCase5|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageCase5[]    findAll()
 * @method AnlageCase5[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Case5Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageCase5::class);
    }

    public function findCase5(Anlage $anlage, $inverter, $stamp)
    {
        $result = $this->createQueryBuilder('c5')
            ->andWhere('c5.anlage = :anlage and c5.inverter = :inverter')
            ->andWhere('c5.stampFrom < :stamp and c5.stampTo >= :stamp')
            ->setParameter('anlage', $anlage)
            ->setParameter('inverter', $inverter)
            ->setParameter('stamp', $stamp)
            ->select('count(c5.inverter)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        return ($result >= 1);
    }

    public function findAllCase5(Anlage $anlage, $from, $to)
    {
        $result = $this->createQueryBuilder('c5')
            ->andWhere('c5.anlage = :anlage')
            ->andWhere('c5.stampFrom BETWEEN :from AND :to')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult()
        ;
        return $result;
    }

    public function countCase5DayAnlage(Anlage $anlage, $day):int
    {
        $startMonth = date('Y-m-d 00:00', strtotime($day));
        $endMonth = date("Y-m-d 23:59", strtotime($day));
        return $this->createQueryBuilder('c5')
            ->andWhere('c5.anlage = :anlage')
            ->andWhere('c5.stampFrom >= :start and c5.stampTo <= :end')
            ->setParameter('anlage', $anlage)
            ->setParameter('start', $startMonth)
            ->setParameter('end', $endMonth)
            ->select('count(c5.inverter)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findAllAnlageDay(Anlage $anlage, $day)
    {
        $lastDayMonth = date('t', strtotime($day));
        $startMonth = date('Y-m-01 00:00', strtotime($day));
        $endMonth = date("Y-m-$lastDayMonth 23:59", strtotime($day));
        $result = $this->createQueryBuilder('c5')
            ->andWhere('c5.anlage = :anlage')
            ->andWhere('c5.stampFrom >= :start and c5.stampTo <= :end')
            ->setParameter('anlage', $anlage)
            ->setParameter('start', $startMonth)
            ->setParameter('end', $endMonth)
            ->getQuery()
            ->getResult()
            ;

        return $result;
    }
}
