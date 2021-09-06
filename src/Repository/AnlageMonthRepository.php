<?php

namespace App\Repository;

use App\Entity\AnlageMonth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageMonth|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageMonth|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageMonth[]    findAll()
 * @method AnlageMonth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageMonthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageMonth::class);
    }

}
