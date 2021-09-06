<?php

namespace App\Repository;

use App\Entity\AnlageAvailability;
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

}
