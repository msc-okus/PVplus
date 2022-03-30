<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use \Doctrine\ORM\QueryBuilder;
use DateTime;

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
            ->andWhere("s.anlage = :anlage")
            ->andWhere("s.stamp BETWEEN :from AND :to")
            ->addOrderBy('s.inverter+0', 'ASC')
            ->addOrderBy('s.stamp', 'DESC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAvailabilityForCase5($anlageID, $day, $inverter)
    {
        $q = $this->createQueryBuilder('c5') // c5 = case5
            ->andWhere('c5.anlage = :anlage_id')
            ->andWhere('c5.inverter = :inverter')
            ->andWhere('c5.stamp = :day')
            ->setParameter('anlage_id', $anlageID)
            ->setParameter('day', $day)
            ->setParameter('inverter', $inverter)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        return $q;
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
                ->getResult()
            ;
        } else {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp BETWEEN :from AND :to AND a.inverter = :inverter')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->setParameter('inverter', $inverter+1)
                ->groupBy('a.inverter')
                ->orderBy('a.inverter*1')
                ->select('a.inverter, sum(a.case_0)as case0, sum(a.case_1) as case1, sum(a.case_2) as case2, sum(a.case_3) as case3, sum(a.case_4) as case4, sum(a.case_5) as case5, sum(a.case_6) as case6, sum(a.control) as control')
                ->getQuery()
                ->getResult()
            ;
        }

        return $result;
    }
}
