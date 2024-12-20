<?php

namespace App\Repository;

use App\Entity\AnlageSunShading;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageSunShading|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageSunShading|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageSunShading[]    findAll()
 * @method AnlageSunShading[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageSunShadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageSunShading::class);
    }
    /**
     * @return AnlageSunShading Returns an array of AnlageSunShading objects
     */
    public function add(AnlageSunShading $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(AnlageSunShading $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }



//    /**
//     * @return AnlageSunShading[] Returns an array of AnlageSunShading objects
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

//    public function findOneBySomeField($value): ?AnlageSunShading
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
