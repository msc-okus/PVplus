<?php

namespace App\Repository;

use App\Entity\NotificationInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationInfo>
 *
 * @method NotificationInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationInfo[]    findAll()
 * @method NotificationInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationInfo::class);
    }

//    /**
//     * @return NotificationInfo[] Returns an array of NotificationInfo objects
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

//    public function findOneBySomeField($value): ?NotificationInfo
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
