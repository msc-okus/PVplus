<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageGridMeterDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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
        $from = date('Y-m-d', strtotime((string) $from));
        try {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.stamp = :from')
                ->andWhere('a.gridMeterValue > 0')
                ->setParameter('anlage', $anlage)
                ->setParameter('from', $from)
                ->select('SUM(a.gridMeterValue) AS eGrid')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
            $result = 0;
        }

        return $result;
    }

    public function sumByDateRange(Anlage $anlage, $from, $to)
    {
        $from = date('Y-m-d', strtotime((string) $from));
        $to = date('Y-m-d', strtotime((string) $to));
        try {
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
        } catch (NoResultException|NonUniqueResultException) {
            $result = 0;
        }

        return $result;
    }

    public function getDateRange($anlage, $from, $to)
    {
        $from = date('Y-m-d', strtotime((string) $from));
        $to = date('Y-m-d', strtotime((string) $to));

        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp >= :from AND a.stamp <= :to')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.stamp', 'ASC')
            ->select('a.stamp, a.gridMeterValue AS eGrid')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
