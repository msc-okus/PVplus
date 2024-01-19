<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageGroups|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageGroups|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageGroups[]    findAll()
 * @method AnlageGroups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageGroups::class);
    }

    public function save(AnlageGroups $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * @return AnlageGroups[]
     */
    public function findAllWeatherStations(Anlage $anlage, $exclude = null)
    {
        $qb = $this->createQueryBuilder('ws')
            ->andWhere('ws.anlage = :anlage')
            ->andWhere('ws.weatherStation != :null')
            ->groupBy('ws.weatherStation')
            ->setParameter('anlage', $anlage)
            ->setParameter('null', 0)
        ;
        if ($exclude) {
            $qb->andWhere('ws.weatherStation != :exclude')
                ->setParameter('exclude', $exclude);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllOrderedByAscNameQueryBuilder()
    {
        return $this->createQueryBuilder('g')->orderBy('g.dcGroupName', 'ASC');
    }

    public  function findByAnlageQueryBuilder(?Anlage $anlage = null):QueryBuilder
    {

        return $this->createQueryBuilder('g')
                ->andWhere('g.anlage =:anlage')
                ->setParameter('anlage', $anlage)
                ->orderBy('g.dcGroup','ASC')
            ;
    }


    public  function searchGroupByAnlageQueryBuilder(Anlage $anlage , ?string $term):QueryBuilder
    {

        $qb= $this->createQueryBuilder('g')
            ->andWhere('g.anlage =:anlage')
            ->setParameter('anlage', $anlage)

            ;

        if ($term && $term !=='') {
            $qb->andWhere('g.dcGroupName LIKE :term')
                ->setParameter('term', '%'.$term.'%');

        }
        return $qb->orderBy('g.dcGroupName','ASC');
    }


}
