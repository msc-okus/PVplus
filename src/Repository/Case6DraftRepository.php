<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\Case6Draft;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Case6Draft|null find($id, $lockMode = null, $lockVersion = null)
 * @method Case6Draft|null findOneBy(array $criteria, array $orderBy = null)
 * @method Case6Draft[]    findAll()
 * @method Case6Draft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Case6DraftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Case6Draft::class);
    }

    public function findAllByAnlage(Anlage $anlage){

        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult();
            ;
}
    // /**
    //  * @return Case6Draft[] Returns an array of Case6Draft objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Case6Draft
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
