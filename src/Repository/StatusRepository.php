<?php

namespace App\Repository;

use App\Entity\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Status::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByanlageDate($anlage, $date, $isWeather)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.Anlage = :anl')
            ->andWhere('s.stamp = :date')
            ->andWhere('s.isWeather = :isWeather')
            ->setParameter('anl', $anlage)
            ->setParameter('date', $date)
            ->setParameter('isWeather', $isWeather)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findLastOfDay($anlage, $yesterday, $today, $isWeather)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.Anlage = :anl')
            ->andWhere('s.stamp > :yesterday')
            ->andWhere('s.stamp < :today')
            ->andWhere('s.isWeather = :isWeather')
            ->setParameter('anl', $anlage)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('today', $today)
            ->setParameter('isWeather', $isWeather)
            ->orderBy('s.stamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
