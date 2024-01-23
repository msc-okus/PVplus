<?php

namespace App\Repository;

use App\Entity\AnlagePpcs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnlagePpcs>
 *
 * @method AnlagePpcs|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagePpcs|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagePpcs[]    findAll()
 * @method AnlagePpcs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlagePpcsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagePpcs::class);
    }

    public function save(AnlagePpcs $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AnlagePpcs $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return AnlagePpcs[] Returns an array of AnlagePpcs objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AnlagePpcs
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
