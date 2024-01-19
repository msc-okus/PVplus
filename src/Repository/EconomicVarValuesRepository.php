<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\EconomicVarValues;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EconomicVarValues|null find($id, $lockMode = null, $lockVersion = null)
 * @method EconomicVarValues|null findOneBy(array $criteria, array $orderBy = null)
 * @method EconomicVarValues[]    findAll()
 * @method EconomicVarValues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EconomicVarValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EconomicVarValues::class);
    }

    public function findOneByDate($year, $month)
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.year = :year')
            ->andWhere('e.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->addSelect('e');

        return $qb->getQuery()
            ->getResult();
    }

    public function findByAnlage(Anlage $anlage)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.anlage = :anlage')
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
        ;
    }
    public function findByAnlageYear(Anlage $anlage, string $year)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.anlage = :anlage')
            ->andWhere('e.year = :year')
            ->setParameter('anlage', $anlage)
            ->setParameter('year', $year)
            ->orderBy('e.Month', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
