<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageForcast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageForcast|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageForcast|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageForcast[]    findAll()
 * @method AnlageForcast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForcastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageForcast::class);
    }

    public function calcForecastByDate(Anlage $anlage, \DateTime $date)
    {
        $week = $date->format('W');

        $forecastSum = $this->createQueryBuilder('f')
            ->andWhere('f.anlage = :anlageId and f.week <= :week')
            ->setParameter('anlageId', $anlage->getAnlId())
            ->setParameter('week', $week)
            ->select('SUM(f.expectedWeek)')
            ->getQuery()
            ->getSingleScalarResult()
            ;
        return $forecastSum;
    }

}
