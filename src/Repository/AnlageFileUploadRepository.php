<?php

namespace App\Repository;

use App\Entity\AnlageFileUpload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageFileUpload|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageFileUpload|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageFileUpload[]    findAll()
 * @method AnlageFileUpload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageFileUploadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageFileUpload::class);
    }

    /**
     * @return AnlageFileUpload []
     */
    public function findIdLike($like)
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.plant_id IN (:val)")
            ->orderBy('a.plant_id', 'ASC')
            ->setParameter('val', $like)
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?AnlageFileUpload
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
