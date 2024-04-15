<?php

namespace App\Repository;

use App\Entity\NotificationWork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationWork>
 *
 * @method NotificationWork|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationWork|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationWork[]    findAll()
 * @method NotificationWork[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationWorkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationWork::class);
    }

//    /**
//     * @return NotificationWork[] Returns an array of NotificationWork objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NotificationWork
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
