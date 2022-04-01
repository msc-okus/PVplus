<?php

namespace App\Repository;

use App\Entity\AlertParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AlertParams|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlertParams|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlertParams[]    findAll()
 * @method AlertParams[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertParamsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertParams::class);
    }

    // /**
    //  * @return AlertParams[] Returns an array of AlertParams objects
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
    public function findOneBySomeField($value): ?AlertParams
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
