<?php

namespace App\Repository;

use App\Entity\Eigner;
use App\Entity\User;
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

    public function findAllDashboard(): array
    {
        return $this->createQueryBuilder('eigner')
            ->innerJoin('eigner.anlage', 'anlage')
            ->addSelect('anlage')
            ->leftJoin('anlage.economicVarNames', 'varName')
            ->leftJoin('anlage.economicVarValues', 'ecoValu')
            ->leftJoin('anlage.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->andWhere('eigner.active = 1')
            ->orderBy('eigner.firma', 'ASC')
            ->addOrderBy('anlage.anlName', 'ASC')
            ->getQuery()
            ->getResult();

    }
    public function findOperations(): array
    {
        return $this->createQueryBuilder('eigner')
            ->innerJoin('eigner.anlage', 'anlage')
            ->addSelect('anlage')
            ->leftJoin('anlage.economicVarNames', 'varName')
            ->leftJoin('anlage.economicVarValues', 'ecoValu')
            ->leftJoin('anlage.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->andWhere('eigner.active = 1')
            ->andWhere('eigner.operations = 1')
            ->orderBy('eigner.firma', 'ASC')
            ->addOrderBy('anlage.anlName', 'ASC')
            ->getQuery()
            ->getResult();

    }


    public static function activeAnlagenCriteria($role): Criteria
    {
        if ($role === true) {
            return Criteria::create()
                ->andWhere(Criteria::expr()->eq('anlHidePlant', 'No'));
        } else {
            return Criteria::create()
                ->andWhere(Criteria::expr()->eq('anlHidePlant', 'No'))
                ->andWhere(Criteria::expr()->eq('anlView', 'Yes'));
        }
    }


    public function getWithSearchQueryBuilder(?string $term): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        if ($term) {
            $qb->andWhere('a.firma LIKE :term OR a.plz LIKE :term OR a.ort LIKE :term')
                ->setParameter('term', '%'.$term.'%')
            ;
        }

        return $qb->orderBy('a.firma', 'ASC');
    }




}
