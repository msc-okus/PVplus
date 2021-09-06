<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenPR;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineExtensions\Query\Mysql;
use Symfony\Component\Validator\Constraints\DateTime;
/**
     * @method AnlagenPR|null find($id, $lockMode = null, $lockVersion = null)
     * @method AnlagenPR|null findOneBy(array $criteria, array $orderBy = null)
     * @method AnlagenPR[]    findAll()
     * @method AnlagenPR[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
class PRRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenPR::class);
    }
    public function findPRInMonth(Anlage $anlage, $month, $year)
    {
        $from = "$year-$month-01";
        $to = date("Y-m-t", strtotime($from));
        $result = $this->createQueryBuilder('pr')
            ->andWhere("pr.anlage = :anlage AND pr.stamp BETWEEN :from AND :to")
            ->orderBy('pr.stamp', 'ASC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
        ;
        return $result;
    }

    public function findPrAnlageDate($anlage, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere("pr.anlage = :anlage")
            ->andWhere("pr.stamp BETWEEN :from AND :to")
            ->orderBy('pr.stamp', 'ASC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findPrAnlageDateIntervall($anlage, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->select(date_format(DateTime::DEFAULT_GROUP, '%M'))
            #->date_format("pr.stamp", '%M')
            ->andWhere("pr.anlage = :anlage")
            ->andWhere("pr.stamp BETWEEN :from AND :to")
            ->groupBy('pr.stamp')
            ->orderBy('pr.stamp', 'ASC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', "$from")
            ->setParameter('to', "$to")
            ->getQuery()
            ->getResult()
            ;
    }

    public function sumAvailabilityByRange($anlagenId, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlageId and pr.stamp >= :from and pr.stamp <= :to')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(pr.plantAvailability)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAvailabilityPerYear($anlagenId, $year, $to)
    {
        $from = "$year-01-01";
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlageId and pr.stamp >= :from and pr.stamp <= :to')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(pr.plantAvailability)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAvailabilitySecondPerYear($anlagenId, $year, $to)
    {
        $from = "$year-01-01";
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlageId and pr.stamp >= :from and pr.stamp <= :to')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(pr.plantAvailabilitySecond)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAvailabilityPerPac($anlagenId, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlageId and pr.stamp >= :from and pr.stamp <= :to')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(pr.plantAvailability)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumAvailabilitySecondPerPac($anlagenId, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.anlage = :anlageId and pr.stamp >= :from and pr.stamp <= :to')
            ->setParameter('anlageId', $anlagenId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('SUM(pr.plantAvailabilitySecond)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function anzRecordsPRPerYear($anlage, $year, $to)
    {
        $from = "$year-01-01";
        return $this->createQueryBuilder('pr')
            ->andWhere("pr.anlage = :anlage")
            ->andWhere("pr.stamp BETWEEN :from AND :to")
            ->andWhere("pr.plantAvailability != 0")
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('count(pr.stamp)')
            ->getQuery()
            ->getSingleScalarResult();
            ;
    }
    public function anzRecordsPRPerPac($anlage, $from, $to)
    {
        return $this->createQueryBuilder('pr')
            ->andWhere("pr.anlage = :anlage")
            ->andWhere("pr.stamp BETWEEN :from AND :to")
            ->andWhere("pr.plantAvailability != 0")
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->select('count(pr.stamp)')
            ->getQuery()
            ->getSingleScalarResult();
        ;
    }
}