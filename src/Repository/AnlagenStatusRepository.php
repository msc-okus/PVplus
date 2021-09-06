<?php


namespace App\Repository;

use App\Entity\AnlagenStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnlagenStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenStatus[]    findAll()
 * @method AnlagenStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlagenStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnlagenStatus::class);
    }

    /**
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term): QueryBuilder {
        $qb = $this->createQueryBuilder('c');

        if ($term) {
            $qb->andWhere('c.name LIKE :term OR c.plz LIKE :term OR c.ort LIKE :term')
                ->setParameter('term', '%' . $term . '%')
            ;
        }

        return $qb->orderBy('c.name', 'DESC');
    }

    public function findStatusAnlageDate($anlage, $from, $to)
    {
        return $this->createQueryBuilder('s')
            ->andWhere("s.anlage = :anlage")
            ->andWhere("s.stamp BETWEEN :from AND :to")
            ->orderBy('s.stamp', 'DESC')
            ->setParameter('anlage', $anlage)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findLastByAnlagenId($id) {

        return $this->createQueryBuilder('o')
            ->andWhere("o.anlId = :id")
            ->orderBy('o.stamp', 'DESC')
            ->setMaxResults(1)
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
            ;
    }


}
