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

    public function sumAvailabilityPerDay($anlagenId, $day): float|bool|int|string|null
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
            ->select('s.stamp, s.inverter, 
                SUM(s.case_0_0) as case00, SUM(s.case_1_0) as case10, SUM(s.case_2_0) as case20, SUM(s.case_3_0) as case30, SUM(s.case_4_0) as case40, SUM(s.case_5_0) as case50, SUM(s.case_6_0) as case60, SUM(s.control_0) as control0, SUM(s.invAPart1_0) as invAPart10, AVG(s.invAPart2_0) as invAPart20, SUM(s.invA_0) as invA0, 
                SUM(s.case_0_1) as case01, SUM(s.case_1_1) as case11, SUM(s.case_2_1) as case21, SUM(s.case_3_1) as case31, SUM(s.case_4_1) as case41, SUM(s.case_5_1) as case51, SUM(s.case_6_1) as case61, SUM(s.control_1) as control1, SUM(s.invAPart1_1) as invAPart11, AVG(s.invAPart2_1) as invAPart21, SUM(s.invA_1) as invA1,
                SUM(s.case_0_2) as case02, SUM(s.case_1_2) as case12, SUM(s.case_2_2) as case22, SUM(s.case_3_2) as case32, SUM(s.case_4_2) as case42, SUM(s.case_5_2) as case52, SUM(s.case_6_2) as case62, SUM(s.control_2) as control2, SUM(s.invAPart1_2) as invAPart12, AVG(s.invAPart2_2) as invAPart22, SUM(s.invA_2) as invA2, 
                SUM(s.case_0_3) as case03, SUM(s.case_1_3) as case13, SUM(s.case_2_3) as case23, SUM(s.case_3_3) as case33, SUM(s.case_4_3) as case43, SUM(s.case_5_3) as case53, SUM(s.case_6_3) as case63, SUM(s.control_3) as control3, SUM(s.invAPart1_3) as invAPart13, AVG(s.invAPart2_3) as invAPart23, SUM(s.invA_3) as invA3')

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

    public function getPaByDate(Anlage $anlage, DateTime $from, DateTime $to, ?int $inverter = null, ?int $departement = null)
    {
        if ($inverter === null) {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp >= :from AND a.stamp <= :to')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->orderBy("a.inverter * 1, a.stamp");
        } else {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp >= :from AND a.stamp <= :to AND a.inverter = :inverter')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from->format('Y-m-d H:i'))
                ->setParameter('to', $to->format('Y-m-d H:i'))
                ->setParameter('inverter', $inverter)
                ->orderBy("a.inverter * 1, a.stamp");
        }

        match ($departement) {
            0 => $result
                ->groupBy("a.inverter")
                ->select("a.stamp, a.inverter,
                    sum(a.case_0_0) as case_0, sum(a.case_1_0) as case_1, sum(a.case_2_0) as case_2, sum(a.case_3_0) as case_3, sum(a.case_4_0) as case_4, sum(a.case_5_0) as case_5, sum(a.case_6_0) as case_6, sum(a.control_0) as control"),
            1 => $result
                ->groupBy("a.inverter")
                ->select("a.stamp, a.inverter,
                    sum(a.case_0_1) as case_0, sum(a.case_1_1) as case_1, sum(a.case_2_1) as case_2, sum(a.case_3_1) as case_3, sum(a.case_4_1) as case_4, sum(a.case_5_1) as case_5, sum(a.case_6_1) as case_6, sum(a.control_1) as control"),
            2 => $result
                ->groupBy("a.inverter")
                ->select("a.stamp, a.inverter,
                    sum(a.case_0_2) as case_0, sum(a.case_1_2) as case_1, sum(a.case_2_2) as case_2, sum(a.case_3_2) as case_3, sum(a.case_4_2) as case_4, sum(a.case_5_2) as case_5, sum(a.case_6_2) as case_6, sum(a.control_2) as control"),
            3 => $result
                ->groupBy("a.inverter")
                ->select("a.stamp, a.inverter,
                    sum(a.case_0_3) as case_0, sum(a.case_1_3) as case_1, sum(a.case_2_3) as case_2, sum(a.case_3_3) as case_3, sum(a.case_4_3) as case_4, sum(a.case_5_3) as case_5, sum(a.case_6_3) as case_6, sum(a.control_3) as control"),
            default => $result->getQuery()->getResult(),
        };

        return $result->getQuery()->getResult();
    }
}
