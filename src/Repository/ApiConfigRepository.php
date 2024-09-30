<?php

namespace App\Repository;

use App\Entity\ApiConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiConfig>
 *
 * @method ApiConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiConfig[]    findAll()
 * @method ApiConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiConfig::class);
    }

    /**
     * @return ContactInfo[] Returns an array of ContactInfo objects
     */
    public function findByOwnerId($value): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.ownerId = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneById($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

}
