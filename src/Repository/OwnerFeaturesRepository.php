<?php

namespace App\Repository;

use App\Entity\OwnerFeatures;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OwnerFeatures>
 *
 * @method OwnerFeatures|null find($id, $lockMode = null, $lockVersion = null)
 * @method OwnerFeatures|null findOneBy(array $criteria, array $orderBy = null)
 * @method OwnerFeatures[]    findAll()
 * @method OwnerFeatures[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwnerFeaturesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwnerFeatures::class);
    }

    public function add(OwnerFeatures $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OwnerFeatures $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OwnerFeatures[] Returns an array of OwnerFeatures objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OwnerFeatures
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
