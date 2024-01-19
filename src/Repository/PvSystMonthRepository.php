<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenPvSystMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagenPvSystMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenPvSystMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenPvSystMonth[]    findAll()
 * @method AnlagenPvSystMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PvSystMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenPvSystMonth::class);
    }

    public function findAllPac(Anlage $anlage, $month)
    {
        $startMonth = (int) $anlage->getPacDate()->format('m');
        $startMonth2 = 0;
        $endMonth2 = 0;
        if ($month < $startMonth) {
            $endMonth = 12;
            $startMonth2 = 1;
            $endMonth2 = $month;
        } else {
            $endMonth = $month;
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('(a.month >= :startMonth AND a.month <= :month) OR (a.month >= :startMonth2 AND a.month <= :endMonth2)')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $endMonth)
            ->setParameter('startMonth', $startMonth)
            ->setParameter('endMonth2', $endMonth2)
            ->setParameter('startMonth2', $startMonth2)
            ->orderBy('a.month', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllYear(Anlage $anlage, $month)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.month >= 1 AND a.month <= :month')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $month)
            ->orderBy('a.month', 'ASC')

            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findOneMonth(Anlage $anlage, $month)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.month = :month')
            ->setParameter('anlage', $anlage)
            ->setParameter('month', $month)
            ->orderBy('a.month', 'ASC')

            ->getQuery()
            ->getSingleResult();
    }

    public function findOneByQuarter($anlage, $quarter)
    {
        if ($quarter == 1) {
            return $this->createQueryBuilder('a')
            ->andWhere('a.month >=1')
            ->andWhere('a.month <= 3')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->addSelect('SUM(a.ertragDesign) as ertrag_design ')

            ->getQuery()
            ->getSingleResult();
        } elseif ($quarter == 2) {
            return $this->createQueryBuilder('a')
            ->andWhere('a.month >=4')
            ->andWhere('a.month <= 6')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->addSelect('SUM(a.ertragDesign) as ertrag_design ')

            ->getQuery()
            ->getSingleResult();
        } elseif ($quarter == 3) {
            return $this->createQueryBuilder('a')
            ->andWhere('a.month >=7')
            ->andWhere('a.month <= 9')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->addSelect('SUM(a.ertragDesign) as ertrag_design ')

            ->getQuery()
            ->getSingleResult();
        } elseif ($quarter == 4) {
            return $this->createQueryBuilder('a')
            ->andWhere('a.month >=10')
            ->andWhere('a.month <= 12')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->addSelect('SUM(a.ertragDesign) as ertrag_design ')

            ->getQuery()
            ->getSingleResult();
        } else {
            return null;
        }
    }

    public function findOneByInterval(Anlage $anlage, $from, $to)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.month >= :from')
            ->andWhere('a.month <=  :to')
            ->andWhere('a.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->addSelect('SUM(a.ertragDesign) as ertrag_design ')

            ->getQuery()
            ->getSingleResult();
    }
}
