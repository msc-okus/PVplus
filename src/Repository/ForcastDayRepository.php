<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageForcastDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageForcastDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageForcastDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageForcastDay[]    findAll()
 * @method AnlageForcastDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForcastDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageForcastDay::class);
    }

    public function findForcastDayByMonth(Anlage $anlage, int $month)
    {
        $firstDayMonth = date('z', strtotime("2001-$month-01"));
        $daysInMonth = date('t', strtotime("2001-$month-01"));
        $lastDayMonth = date('z', strtotime("2001-$month-$daysInMonth"));
        $forecast = $this->createQueryBuilder('f')
            ->andWhere('f.anlage = :anlageId AND f.day <= :lastDay AND f.day >= :firstDay')
            ->setParameter('anlageId', $anlage->getAnlId())
            ->setParameter('firstDay', (int)$firstDayMonth + 1) // plus 1, because 'date' count the first day in year with 0
            ->setParameter('lastDay', (int)$lastDayMonth + 1) // plus 1, because 'date' count the first day in year with 0
            ->getQuery()
            ->getResult()
        ;

        return $forecast;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function calcForecastByDate(Anlage $anlage, \DateTime $date)
    {
        $forecastSum = $this->createQueryBuilder('f')
            ->andWhere('f.anlage = :anlageId and f.day <= :day')
            ->setParameter('anlageId', $anlage->getAnlId())
            ->setParameter('day', $date->format('z'))
            ->select('SUM(f.factorDay)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $forecastSum * $anlage->getContractualGuarantiedPower();
    }
}
