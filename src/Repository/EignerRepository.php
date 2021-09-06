<?php

namespace App\Repository;


use App\Entity\Eigner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Eigner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Eigner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Eigner[]    findAll()
 * @method Eigner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EignerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eigner::class);
    }


    public static function activeAnlagenCriteria($role): Criteria
    {
        if ($role == true) {
            return Criteria::create()
                ->andWhere(Criteria::expr()->eq('anlHidePlant', 'No'));
        } else {
            return Criteria::create()
                ->andWhere(Criteria::expr()->eq('anlHidePlant', 'No'))
                ->andWhere(Criteria::expr()->eq('anlView', 'Yes'));
        }

    }

    /**
     * @return Eigner[]
     */
    public function findAllDashboard()
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.anlage', 'b')
            ->addSelect('b')
            ->andWhere('a.active = 1')
            ->orderBy('b.anlName', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term): QueryBuilder {
        $qb = $this->createQueryBuilder('a');

        if ($term) {
            $qb->andWhere('a.firma LIKE :term OR a.plz LIKE :term OR a.ort LIKE :term')
                ->setParameter('term', '%' . $term . '%')
            ;
        }

        return $qb->orderBy('a.firma', 'ASC');
    }
}
