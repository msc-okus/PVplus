<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenMonthlyData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagenMonthlyData|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenMonthlyData|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenMonthlyData[]    findAll()
 * @method AnlagenMonthlyData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonthlyDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenMonthlyData::class);
    }

    public function findSumByYear(Anlage $anlage, $to)
    {
        $toYear = $to->format('Y');
        $toMonth = $to->format('m');

        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.year = :year AND a.month <= :month')
            ->setParameter('year', $toYear)
            ->setParameter('month', $toMonth)
            ->setParameter('anlage', $anlage)
            ->select('SUM(a.externMeterDataMonth)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Query all record between to dates.
     * Entytie stores no real DateTime only month and year.
     * Creates from month and year a number (Year + (Month / 10)), this allows to search very easy.
     *
     * @return int|mixed|string
     */
    public function findByDateRange(Anlage $anlage, \DateTime $from, \DateTime $to)
    {
        $startYear = (int) $from->format('Y');
        $startMonth = (int) $from->format('m');
        $endYear = (int) $to->format('Y');
        $endMonth = (int) $to->format('m');

        $start = $startYear + ($startMonth / 100);
        $end = $endYear + ($endMonth / 100);

        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('((a.year + a.month / 100) >= :start AND (a.year + a.month / 100) <= :end)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage)
            ->orderBy('a.year, a.month', 'asc')
            ->getQuery()
        ;

        return $result->getResult();
    }

    public function findSumByPac(Anlage $anlage, \DateTime $pacDate, \DateTime $to)
    {
        $pacYear = $pacDate->format('Y');
        $pacMonth = $pacDate->format('m');
        $toYear = $to->format('Y');
        $toMonth = $to->format('m');

        if ($toYear > $pacYear) {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('(a.year >= :toYear AND a.month <= :toMonth) OR (a.year >= :pacYear AND a.month >= :pacMonth)')
                ->setParameter('toYear', $toYear)
                ->setParameter('toMonth', $toMonth)
                ->setParameter('pacYear', $pacYear)
                ->setParameter('pacMonth', $pacMonth)
                ->setParameter('anlage', $anlage)
                ->select('SUM(a.externMeterDataMonth)')
                ->getQuery()
                ->getSingleScalarResult();
        } else {
            $result = $this->createQueryBuilder('a')
                ->andWhere('a.anlage = :anlage')
                ->andWhere('a.year >= :pacYear AND a.month >= :pacMonth')
                ->setParameter('pacYear', $pacYear)
                ->setParameter('pacMonth', $pacMonth)
                ->setParameter('anlage', $anlage)
                ->select('SUM(a.externMeterDataMonth)')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $result;
    }

    public function findSumByDate(Anlage $anlage, \DateTime $date)
    {
        $year = $date->format('Y');
        $month = $date->format('m');

        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.year < :year OR (a.year = :year AND a.month <= :month)')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('anlage', $anlage)
            ->select('SUM(a.externMeterDataMonth)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
