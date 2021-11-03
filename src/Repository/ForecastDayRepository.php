<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageForecastDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageForecastDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageForecastDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageForecastDay[]    findAll()
 * @method AnlageForecastDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageForecastDay::class);
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
