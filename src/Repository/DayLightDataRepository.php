<?php

namespace App\Repository;

use App\Entity\DayLightData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DayLightData|null find($id, $lockMode = null, $lockVersion = null)
 * @method DayLightData|null findOneBy(array $criteria, array $orderBy = null)
 * @method DayLightData[]    findAll()
 * @method DayLightData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DayLightDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DayLightData::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(DayLightData $entity, bool $flush = true): void
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
    public function remove(DayLightData $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return DayLightData[] Returns an array of DayLightData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DayLightData
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOneByDate($date, $anlage): ?DayLightData
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.date = :date')
            ->andWhere('d.anlage = :anl')
            ->setParameter('date', $date)
            ->setParameter('anl', $anlage)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
