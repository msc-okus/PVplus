<?php

namespace App\Repository;

use App\Entity\AnlageGroupMonths;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageGroupMonths|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageGroupMonths|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageGroupMonths[]    findAll()
 * @method AnlageGroupMonths[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupMonthsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageGroupMonths::class);
    }

    // /**
    //  * @return AnlageGroupMonths[] Returns an array of AnlageGroupMonths objects
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
    public function findOneBySomeField($value): ?AnlageGroupMonths
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
