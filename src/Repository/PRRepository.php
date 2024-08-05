<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagenPR|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenPR|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenPR[]    findAll()
 * @method AnlagenPR[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PRRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenPR::class);
    }

    public function findPRInMonth(Anlage $anlage, $month, $year)
    {
        $from = "$year-$month-01";
        $to = date('Y-m-t', strtotime($from));

        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlage AND pr.stamp BETWEEN :from AND :to')
            ->orderBy('pr.stamp', 'ASC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult();
    }

    public function findPrAnlageDate($anlage, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlage')
            ->andWhere('pr.stamp >= :from AND pr.stamp < :to')
            ->orderBy('pr.stamp', 'ASC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
        ;
    }

}
