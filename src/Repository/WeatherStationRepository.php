<?php

namespace App\Repository;

use App\Entity\WeatherStation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WeatherStation|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherStation|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherStation[]    findAll()
 * @method WeatherStation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherStationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherStation::class);
    }

    /**
     * @return WeatherStation[]
     */
    public function findAllUp()
    {
        return $this->createQueryBuilder('w')
            ->andWhere("w.type LIKE 'UP%'")
            ->orderBy('w.databaseIdent', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term): QueryBuilder {
        $qb = $this->createQueryBuilder('a');

        if ($term) {
            $qb->andWhere('a.type LIKE :term OR a.location LIKE :term OR a.databaseIdent LIKE :term')
                ->setParameter('term', '%' . $term . '%')
            ;
        }

        return $qb->orderBy('a.databaseIdent', 'ASC');
    }

}
