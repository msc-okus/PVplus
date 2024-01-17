<?php

namespace App\Repository;

use App\Entity\AlertMessages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AlertMessages|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlertMessages|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlertMessages[]    findAll()
 * @method AlertMessages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertMessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertMessages::class);
    }

    // /**
    //  * @return AlertMessages[] Returns an array of AlertMessages objects
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
    public function findOneBySomeField($value): ?AlertMessages
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
