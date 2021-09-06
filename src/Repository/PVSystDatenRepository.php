<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagePVSystDaten;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function sumByStamp(Anlage $anlage, $stamp)
    {

        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp LIKE :stamp')
            ->andWhere('a.electricityGrid > 0')
            ->setParameter('anlage', $anlage->getAnlId())
            ->setParameter('stamp', $stamp.'%')
            ->select('SUM(a.electricityGrid)/1000 AS eGrid, SUM(a.electricityInverterOut)/1000 AS eInverter')
            ->getQuery()
            ->getScalarResult();

        return $result[0];
    }

    public function sumByDateRange(Anlage $anlage, $from, $to)
    {

        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp BETWEEN :from AND :to')
            ->andWhere('a.electricityGrid > 0')
            ->setParameter('anlage', $anlage->getAnlId())
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(a.electricityGrid)/1000 AS eGrid')
            ->getQuery()
            ->getSingleScalarResult();

        return $result;
    }

}
