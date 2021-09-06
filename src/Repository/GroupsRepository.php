<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    /**
     * @return AnlageGroups[]
     */
    public function findAllWeatherStations(Anlage $anlage, $exclude = null)
    {
        $qb = $this->createQueryBuilder('ws')
            ->andWhere("ws.anlage = :anlage")
            ->andWhere('ws.weatherStation != :null')
            ->groupBy('ws.weatherStation')
            ->setParameter('anlage', $anlage)
            ->setParameter('null', 0)
            ;
        if ($exclude) {
            $qb->andWhere('ws.weatherStation != :exclude')
                ->setParameter('exclude', $exclude);
        }

        return  $qb->getQuery()->getResult();
    }
}
