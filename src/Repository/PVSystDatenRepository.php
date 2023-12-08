<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagePVSystDaten;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagePVSystDaten|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagePVSystDaten|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagePVSystDaten[]    findAll()
 * @method AnlagePVSystDaten[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PVSystDatenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagePVSystDaten::class);
    }

    public function allGreateZero(Anlage $anlage, $from, $to)
    {
        $from = date('Y-m-d 00:00', strtotime((string) $from));
        $to = date('Y-m-d 23:59', strtotime((string) $to));

        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere(' a.stamp >= :from AND a.stamp <= :to') //a.electricityGrid > 0 AND
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.stamp', 'ASC')
            ->getQuery()
            ;
        return $result->getResult();
    }

    public function sumByStamp(Anlage $anlage, $stamp)
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp LIKE :stamp')
            ->andWhere('a.electricityGrid > 0')
            ->setParameter('anlage', $anlage->getAnlId())
            ->setParameter('stamp', $stamp.'%')
            ->select('SUM(a.electricityGrid) AS eGrid, SUM(a.electricityInverterOut) AS eInverter')
            ->getQuery()
            ->getScalarResult();

        return $result[0];
    }


    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function sumGridByDateRange(Anlage $anlage, $from, $to): float|bool|int|string|null
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp >= :from AND a.stamp < :to')
            ->andWhere('a.electricityGrid > 0')
            ->setParameter('anlage', $anlage->getAnlId())
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(a.electricityGrid) AS eGrid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function sumIrrByDateRange(Anlage $anlage, $from, $to): float|bool|int|string|null
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp >= :from AND a.stamp < :to')
            ->andWhere('a.irrGlobalInc > 0')
            ->setParameter('anlage', $anlage->getAnlId())
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(a.irrGlobalInc) AS irr')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
