<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageAcGroups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageAcGroups|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageAcGroups|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageAcGroups[]    findAll()
 * @method AnlageAcGroups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcGroupsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageAcGroups::class);
    }
    public function findByAnlageTrafoNr(Anlage $anlage,  $trafoNr){
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anl')
            ->andWhere('a.trafoNr = :trafoNr')
            ->setParameter('anl', $anlage)
            ->setParameter('trafoNr', $trafoNr)
            ->getQuery();

        return $result->getResult();
    }



    public function getAllTrafoNr(Anlage $anlage){
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anl')
            ->setParameter('anl', $anlage)
            ->groupBy('a.trafoNr')
            ->getQuery();

        return $result->getResult();
    }

}
