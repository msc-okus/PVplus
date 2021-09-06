<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageCase5;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageCase5|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageCase5|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageCase5[]    findAll()
 * @method AnlageCase5[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageCase5Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageCase5::class);
    }

    public function findCase5(Anlage $anlage, $inverter, $stamp)
    {
        $result = $this->createQueryBuilder('c5')
            ->andWhere('c5.anlage = :anlage and c5.inverter = :inverter')
            ->andWhere('c5.stampFrom < :stamp and c5.stampTo >= :stamp')
            ->setParameter('anlage', $anlage)
            ->setParameter('inverter', $inverter)
            ->setParameter('stamp', $stamp)
            ->select('count(c5.inverter)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
        return ($result >= 1);
    }
}
