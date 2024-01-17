<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\EconomicVarNames;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EconomicVarNames|null find($id, $lockMode = null, $lockVersion = null)
 * @method EconomicVarNames|null findOneBy(array $criteria, array $orderBy = null)
 * @method EconomicVarNames[]    findAll()
 * @method EconomicVarNames[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EconomicVarNamesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EconomicVarNames::class);
    }

    public function findByAnlage(string $anlId)
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.anlage = :query')
            ->setParameter('query', $anlId)
            ->addSelect('e');

        return $qb->getQuery()
            ->getResult();
    }

    // /**
    //  * @return EconomicVarNames[] Returns an array of EconomicVarNames objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EconomicVarNames
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findOneByAnlage(Anlage $anlage)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
