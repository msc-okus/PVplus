<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Integer;
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
    public function getWithSearchQueryBuilder(?string $term, ?string $searchstatus, ?string $searchtype, ?string $searchmonth): QueryBuilder
    {
        $qb = $this->createQueryBuilder('report')
            ->innerJoin('report.anlage', 'a')
            ->innerJoin('report.eigner', 'e')
            ->addSelect('a')
            ->addSelect('e')
            ;

        // Wenn Benutzer kein G4N Rolle hat
        if (! $this->security->isGranted('ROLE_G4N')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $granted = explode(',', $user->getGrantedList());

            $qb->andWhere("a.anlId IN (:granted)")
                ->setParameter('granted', $granted)
            ;
        }

        // schließe Archiv und falsche Reports aus
        // muss noch via Backend auswählbar gemacht werden
        $qb->andWhere('report.reportStatus != 9');
        $qb->andWhere('report.reportStatus != 11');
        if ($searchstatus != '') {
            $qb->andWhere("report.reportStatus = $searchstatus");
        }

        if ($searchtype != '') {
            $qb->andWhere("report.reportType like '$searchtype'");
        }

        if ($searchmonth !='') {
            $qb->andWhere("report.month = $searchmonth");
        }

        if ($term != '') {
            $qb ->andWhere(" a.anlName LIKE '$term' ");
        }



        return $qb;
        /*->orderBy('e.firma', 'ASC')
            ->addOrderBy('a.anlName', 'ASC')
            ->addOrderBy('report.reportType')
            ->addOrderBy('report.year')
            ->addOrderBy('report.month');
            */
    }
}
