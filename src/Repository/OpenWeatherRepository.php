<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\OpenWeather;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OpenWeather|null find($id, $lockMode = null, $lockVersion = null)
 * @method OpenWeather|null findOneBy(array $criteria, array $orderBy = null)
 * @method OpenWeather[]    findAll()
 * @method OpenWeather[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OpenWeatherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpenWeather::class);
    }

    /**
     * Sucht einen OpenWeather Eintrag für die angebenen Anlage und den angebenen TimeStamp (nur auf Stunden Basis)
     *
     * @return OpenWeather|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findTimeMatchingOpenWeather(Anlage $anlage, \DateTime $stamp): OpenWeather|null
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anlage')
            ->andWhere('a.stamp = :stamp')
            ->setParameter('anlage', $anlage)
            ->setParameter('stamp', $stamp->format('Y-m-d H:00:00'))
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $qb;
    }

    // /**
    //  * @return AlertMessages[] Returns an array of AlertMessages objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AlertMessages
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
