<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageForcastDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        $firstDayMonth = date('z', strtotime("2000-$month-01"));
        $daysInMonth = date('t', strtotime("2000-$month-01"));
        $lastDayMonth = date('z', strtotime("2000-$month-$daysInMonth"));

        $forecast = $this->createQueryBuilder('f')
            ->andWhere('f.anlage = :anlageId AND f.day <= :lastDay AND f.day >= :firstDay')
            ->setParameter('anlageId', $anlage->getAnlId())
            ->setParameter('firstDay', $firstDayMonth)
            ->setParameter('lastDay', $lastDayMonth)
            ->getQuery()
            ->getResult()
            ;
        return $forecast;
    }

}
