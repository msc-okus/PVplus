<?php

namespace App\Repository;

use App\Entity\AnlageGroupModules;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageGroupModules|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageGroupModules|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageGroupModules[]    findAll()
 * @method AnlageGroupModules[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupModulesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageGroupModules::class);
    }

    // /**
    //  * @return AnlageGroupModules[] Returns an array of AnlageGroupModules objects
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
    public function findOneBySomeField($value): ?AnlageGroupModules
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
