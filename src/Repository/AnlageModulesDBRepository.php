<?php

namespace App\Repository;

use App\Entity\AnlageModulesDB;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageModulesDB|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageModulesDB|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageModulesDB[]    findAll()
 * @method AnlageModulesDB[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageModulesDBRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageModulesDB::class);
    }

    public function add(AnlageModulesDB $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(AnlageModulesDB $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
    public function findAllUp(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere("w.type LIKE 'UP%'")
            ->orderBy('w.databaseIdent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getWithSearchQueryBuilder(?string $term): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        if ($term) {
            $qb->andWhere('a.type LIKE :term OR a.type LIKE :term OR a.id LIKE :term')
                ->setParameter('term', '%'.$term.'%')
            ;
        }

        return $qb->orderBy('a.id', 'ASC');
    }
    // /**
    //  * @return AnlageModules[] Returns an array of AnlageModules objects
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
    public function findOneBySomeField($value): ?AnlageModules
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
