<?php

namespace App\Repository;

use App\Entity\AllowedPlants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AllowedPlants>
 *
 * @method AllowedPlants|null find($id, $lockMode = null, $lockVersion = null)
 * @method AllowedPlants|null findOneBy(array $criteria, array $orderBy = null)
 * @method AllowedPlants[]    findAll()
 * @method AllowedPlants[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AllowedPlantsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AllowedPlants::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AllowedPlants $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(AllowedPlants $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return AllowedPlants[] Returns an array of AllowedPlants objects
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
    public function findOneBySomeField($value): ?AllowedPlants
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
