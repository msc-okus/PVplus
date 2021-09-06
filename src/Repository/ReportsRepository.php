<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method AnlagenReports|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenReports|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenReports[]    findAll()
 * @method AnlagenReports[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportsRepository extends ServiceEntityRepository
{

    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, AnlagenReports::class);
        $this->security = $security;
    }


    /**
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.anlage', 'a')
            ->innerJoin('r.eigner', 'e')
            ->addSelect('a')
            ->addSelect('e')
            ;

        // Wenn Benutzer kein G4N Rolle hat
        if (! $this->security->isGranted('ROLE_G4N')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $accessList = $user->getAccessList();
            $qb->andWhere('r.eigner in (:accessList)')
                ->setParameter('accessList', $accessList);
        }

        // schließe Archiv und falsche Reports aus
        // muss noch via Backend auswählbar gemacht werden
        $qb->andWhere('r.reportStatus != 9');
        $qb->andWhere('r.reportStatus != 11');

        if ($term) {
            $qb ->andWhere('r.reportType LIKE :term or a.anlName LIKE :term or e.firma LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb->orderBy('e.firma', 'ASC')
            ->addOrderBy('a.anlName', 'ASC')
            ->addOrderBy('r.reportType')
            ->addOrderBy('r.year')
            ->addOrderBy('r.month');
    }


}
