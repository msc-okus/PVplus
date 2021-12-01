<?php

namespace App\Repository;

use App\Entity\AnlageFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageFile[]    findAll()
 * @method AnlageFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageFile::class);
    }

    // /**
    //  * @return AnlageFile[] Returns an array of AnlageFile objects
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
    public function findOneBySomeField($value): ?AnlageFile
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
