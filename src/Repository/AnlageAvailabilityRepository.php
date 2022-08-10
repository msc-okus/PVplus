<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageAvailability|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageAvailability|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageAvailability[]    findAll()
 * @method AnlageAvailability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageAvailability::class);
    }

    public function sumAvailabilityPerDay($anlagenId, $day)
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.anlage = :anlageId and pa.stamp = :day')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('day', $day)
            ->select('SUM(pa.invA)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAvailabilitySecondPerDay($anlagenId, $day)
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.anlage = :anlageId and pa.stamp = :day')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('day', $day)
            ->select('SUM(pa.invASecond)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAvailabilityAnlageDate($anlage, $from, $to)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.anlage = :anlage')
            ->andWhere('s.stamp BETWEEN :from AND :to')
            ->addOrderBy('s.inverter+0', 'ASC')
            ->addOrderBy('s.stamp', 'DESC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('s.stamp, s.inverter, SUM(s.case_0) as case0, SUM(s.case_1) as case1, SUM(s.case_2) as case2, SUM(s.case_3) as case3, SUM(s.case_4) as case4, SUM(s.case_5) as case5, SUM(s.case_6) as case6, SUM(s.control) as control, SUM(s.invAPart1) as invApart1, SUM(s.invAPart2) as invApart2, SUM(s.invA) as invA, 
            SUM(s.case_0_second) as case0second, SUM(s.case_1_second) as case1second, SUM(s.case_2_second) as case2second, SUM(s.case_3_second) as case3second, SUM(s.case_4_second) as case4second, SUM(s.case_5_second) as case5second, SUM(s.case_6_second) as case6second, SUM(s.control_second) as control_second, SUM(s.invAPart1_second) as invAPart1Second, SUM(s.invAPart2_second) as invAPart2Second, SUM(s.invASecond) as invASecond')

            ->groupBy('s.inverter')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAvailabilityForCase5($anlageID, $day, $inverter)
    {
        return $this->createQueryBuilder('c5') // c5 = case5
            ->andWhere('c5.anlage = :anlage_id')
            ->andWhere('c5.inverter = :inverter')
            ->andWhere('c5.stamp = :day')
            ->setParameter('anlage_id', $anlageID)
            ->setParameter('day', $day)
            ->setParameter('inverter', $inverter)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function sumAllCasesByDate(Anlage $anlage, DateTime $from, DateTime $to, ?int $inverter = null)
    {
        if ($inverter === null) {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp BETWEEN :from AND :to')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->groupBy('a.inverter')
                ->orderBy('a.inverter*1')
                ->select('a.inverter, sum(a.case_0)as case0, sum(a.case_1) as case1, sum(a.case_2) as case2, sum(a.case_3) as case3, sum(a.case_4) as case4, sum(a.case_5) as case5, sum(a.case_6) as case6, sum(a.control) as control')
                ->getQuery()
            ;
        } else {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp BETWEEN :from AND :to AND a.inverter = :inverter')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->setParameter('inverter', $inverter + 1)
                ->groupBy('a.inverter')
                ->orderBy('a.inverter*1')
                ->select('a.inverter, sum(a.case_0)as case0, sum(a.case_1) as case1, sum(a.case_2) as case2, sum(a.case_3) as case3, sum(a.case_4) as case4, sum(a.case_5) as case5, sum(a.case_6) as case6, sum(a.control) as control')
                ->getQuery()
            ;
        }

        return $result->getResult();
    }
}
