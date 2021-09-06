<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenPvSystMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagenPvSystMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenPvSystMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenPvSystMonth[]    findAll()
 * @method AnlagenPvSystMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PvSystMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenPvSystMonth::class);
    }

    public function findAllPac(Anlage $anlage, $month)
    {
        $startMonth  = (int)$anlage->getPacDate()->format('m');
        $startMonth2 = 0;
        $endMonth2   = 0;
        if ($month < $startMonth) {
            $endMonth = 12;
            $startMonth2 = 1;
            $endMonth2 = $month;
        } else {
            $endMonth = $month;
        }
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('(a.month >= :startMonth AND a.month <= :month) OR (a.month >= :startMonth2 AND a.month <= :endMonth2)')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $endMonth)
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth2', $endMonth2)
            ->setParameter('startMonth2', $startMonth2)
            ->orderBy('a.month', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllYear(Anlage $anlage, $month)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.month >= 1 AND a.month <= :month')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $month)
            ->orderBy('a.month', 'ASC')

            ->getQuery()
            ->getResult()
            ;
    }

    public function findOneMonth(Anlage $anlage, $month)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.month = :month')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $month)
            ->orderBy('a.month', 'ASC')

            ->getQuery()
            ->getSingleResult()
            ;
    }

}
