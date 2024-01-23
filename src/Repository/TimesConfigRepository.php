<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\TimesConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TimesConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimesConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimesConfig[]    findAll()
 * @method TimesConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimesConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimesConfig::class);
    }

    /**
     * @param Anlage $anlage
     * @param $type
     * @param \DateTime $date
     * @return TimesConfig|null Returns an array of TimesConfig objects
     */
    public function findValidConfig(Anlage $anlage, $type, \DateTime $date): TimesConfig|null
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.anlage = :anlage')
            ->andWhere('t.type = :type')
            ->andWhere('(t.startDate <= t.endDate AND t.startDate <= :date AND t.endDate >= :date) OR
                        (t.startDate > t.endDate AND (t.startDate <= :date OR t.endDate >= :date))')
            ->setParameter('anlage', $anlage)
            ->setParameter('type', $type)
            ->setParameter('date', $date->format('2000-n-j'))
            ->setMaxResults('1')
            ->getQuery()
            ->getResult()
        ;

        // Search for fallback if we can not find any other config
        if (!$qb) {
            try {
                $qb = $this->createQueryBuilder('t')
                    ->andWhere('t.anlage IS NULL')
                    ->andWhere('t.type = :type')
                    ->setParameter('type', $type)
                    ->getQuery()
                    ->getSingleResult();
            } catch (NoResultException | NonUniqueResultException) {
                return null;
            }
            return $qb;
        }

        return $qb[0];
    }
}
