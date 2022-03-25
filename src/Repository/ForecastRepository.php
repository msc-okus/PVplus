<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageForecast|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageForecast[]    findAll()
 * @method AnlageForecast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageForecast::class);
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
