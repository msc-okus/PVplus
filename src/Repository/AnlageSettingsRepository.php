<?php

namespace App\Repository;

use App\Entity\AnlageSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageSettings[]    findAll()
 * @method AnlageSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageSettings::class);
    }

    // /**
    //  * @return AnlageSettings[] Returns an array of AnlageSettings objects
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
    public function findOneBySomeField($value): ?AnlageSettings
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
