<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageGridMeterDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageGridMeterDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageGridMeterDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageGridMeterDay[]    findAll()
 * @method AnlageGridMeterDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GridMeterDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageGridMeterDay::class);
    }

    public function sumByDate(Anlage $anlage, $from)
    {
        $from = date('Y-m-d', strtotime($from));
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp = :from')
            ->andWhere('a.gridMeterValue > 0')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->select('SUM(a.gridMeterValue) AS eGrid')
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }

    public function sumByDateRange(Anlage $anlage, $from, $to)
    {
        $from = date('Y-m-d', strtotime($from));
        $to = date('Y-m-d', strtotime($to));
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp >= :from AND a.stamp <= :to')
            ->andWhere('a.gridMeterValue > 0')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(a.gridMeterValue) AS eGrid')
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }
}
