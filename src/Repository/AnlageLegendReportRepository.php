<?php

namespace App\Repository;

use App\Entity\AnlageLegendReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageLegendReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageLegendReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageLegendReport[]    findAll()
 * @method AnlageLegendReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageLegendReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageLegendReport::class);
    }

    // /**
    //  * @return AnlageLegendReport[] Returns an array of AnlageLegendReport objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AnlageLegendReport
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
