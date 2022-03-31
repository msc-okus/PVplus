<?php

namespace App\Repository;

use App\Entity\Case6Array;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Case6Array|null find($id, $lockMode = null, $lockVersion = null)
 * @method Case6Array|null findOneBy(array $criteria, array $orderBy = null)
 * @method Case6Array[]    findAll()
 * @method Case6Array[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Case6ArrayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Case6Array::class);
    }

    // /**
    //  * @return Case6Array[] Returns an array of Case6Array objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Case6Array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
