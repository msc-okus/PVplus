<?php

namespace App\Repository;

use App\Entity\EconomicVarValues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EconomicVarValues|null find($id, $lockMode = null, $lockVersion = null)
 * @method EconomicVarValues|null findOneBy(array $criteria, array $orderBy = null)
 * @method EconomicVarValues[]    findAll()
 * @method EconomicVarValues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EconomicVarValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EconomicVarValues::class);
    }

    // /**
    //  * @return EconomicVarValues[] Returns an array of EconomicVarValues objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EconomicVarValues
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
