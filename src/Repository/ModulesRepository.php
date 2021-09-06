<?php

namespace App\Repository;

use App\Entity\AnlageModules;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageModules|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageModules|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageModules[]    findAll()
 * @method AnlageModules[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModulesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageModules::class);
    }

    // /**
    //  * @return AnlageModules[] Returns an array of AnlageModules objects
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
    public function findOneBySomeField($value): ?AnlageModules
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
