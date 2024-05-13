<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlageFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlageFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlageFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlageFile[]    findAll()
 * @method AnlageFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlageFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlageFile::class);
    }
    public function getWithSearchQueryBuilder(Anlage $anlage, $name = "", $type = "", $sort = "", $direction = ""){

        $qb = $this->createQueryBuilder('anlage_file')
            ->andWhere("anlage_file.anlage = '$anlage'");
        ;
        if ($name != ""){
            $qb->andWhere("anlage_file.filename LIKE '$name%'" );
        }
        if ($type != ""){
            $qb->andWhere("anlage_file.mimeType = '$type'" );
        }
        if ($sort !== "") $qb->addOrderBy($sort, $direction);
        return $qb;
    }
}
