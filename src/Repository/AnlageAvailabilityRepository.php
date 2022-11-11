<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageAvailability;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
        $result =  $this->createQueryBuilder('pa')
            ->andWhere('pa.anlage = :anlageId and pa.stamp = :day')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('day', $day)
            ->select('SUM(pa.invA_1)')
            ->getQuery();

        try {
            return $result->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    public function sumAvailabilitySecondPerDay($anlagenId, $day)
    {
        $result = $this->createQueryBuilder('pa')
            ->andWhere('pa.anlage = :anlageId and pa.stamp = :day')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('day', $day)
            ->select('SUM(pa.invA_2)')
            ->getQuery();

         try {
             return $result->getSingleScalarResult();
         } catch (NoResultException | NonUniqueResultException) {
             return 0;
         }
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
            ->select('s.stamp, s.inverter, SUM(s.case_0_1) as case0, SUM(s.case_1_1) as case1, SUM(s.case_2_1) as case2, SUM(s.case_3_1) as case3, SUM(s.case_4_1) as case4, SUM(s.case_5_1) as case5, SUM(s.case_6_1) as case6, SUM(s.control_1) as control, SUM(s.invAPart1_1) as invApart1, SUM(s.invAPart2_1) as invApart2, SUM(s.invA_1) as invA, 
            SUM(s.case_0_2) as case0second, SUM(s.case_1_2) as case1second, SUM(s.case_2_2) as case2second, SUM(s.case_3_2) as case3second, SUM(s.case_4_2) as case4second, SUM(s.case_5_2) as case5second, SUM(s.case_6_2) as case6second, SUM(s.control_2) as control_second, SUM(s.invAPart1_2) as invAPart1Second, SUM(s.invAPart2_2) as invAPart2Second, SUM(s.invA2) as invASecond')

            ->groupBy('s.inverter')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     */
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

    public function getPaByDate(Anlage $anlage, DateTime $from, DateTime $to, ?int $inverter = null)
    {
        if ($inverter === null) {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp BETWEEN :from AND :to')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->orderBy("a.inverter * 1, a.stamp")
                ->getQuery()
            ;
        } else {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp BETWEEN :from AND :to AND a.inverter = :inverter')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->setParameter('inverter', $inverter)
                ->orderBy("a.inverter * 1, a.stamp")
                ->getQuery()
            ;
        }

        return $result->getResult();
    }
}
