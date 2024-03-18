<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\AnlagenReports;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method AnlagenReports|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnlagenReports|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnlagenReports[]    findAll()
 * @method AnlagenReports[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportsRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
        private AnlagenRepository $anlageRepo)
    {
        parent::__construct($registry, AnlagenReports::class);
    }

    /**
     * @return QueryBuilder
     */
    public function findOneByAMY(Anlage $Anl, string $month, string $year)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlage = :anl')
            ->andWhere("a.reportType = 'am-report'")
            ->andWhere('a.month LIKE :month')
            ->andWhere('a.year = :year')
            ->setParameter('anl', $Anl)
            ->setParameter('month', '%'.$month)
            ->setParameter('year', $year)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getWithSearchQueryBuilderAnalysis(?string $term = '', ?string $searchstatus = '', ?string $searchtype = '', ?string $searchmonth = '', ?string $searchyear = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('report')
            ->innerJoin('report.anlage', 'a')
            ->innerJoin('report.eigner', 'e')
            ->addSelect('a')
            ->addSelect('e')
        ;

        // Wenn Benutzer kein G4N Rolle hat
        if (!$this->security->isGranted('ROLE_G4N')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $granted =  $this->anlageRepo->findAllActiveAndAllowed();

            $qb->andWhere('a.anlId IN (:plantList)')
                ->setParameter('plantList', $granted);
        }

        if ($searchstatus != '') {
            $qb->andWhere("report.reportStatus = $searchstatus");
        }

            $qb->andWhere("report.reportType = 'string-analyse'");

        if ($searchmonth != '') {
            $qb->andWhere("report.month = $searchmonth");
        }
        if ($searchyear != '') {
            $qb->andWhere("report.year = $searchyear");
        }
        if ($term != '') {
            $qb->andWhere(" a.anlName LIKE '$term' ");
        }
        return $qb;
    }
    public function getWithSearchQueryBuilder(?string $term = '', ?string $searchstatus = '', ?string $searchtype = '', ?string $searchmonth = '', ?string $searchyear = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('report')
            ->innerJoin('report.anlage', 'a')
            ->innerJoin('report.eigner', 'e')
            ->addSelect('a')
            ->addSelect('e')
        ;

        // Wenn Benutzer kein G4N Rolle hat
        if (!$this->security->isGranted('ROLE_G4N')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $granted =  $this->anlageRepo->findAllActiveAndAllowed();

            $qb->andWhere('a.anlId IN (:plantList)')
                ->setParameter('plantList', $granted);
            // schließe Archiv und falsche Reports aus
            // muss noch via Backend auswählbar gemacht werden
        }

        if ($searchstatus != '') {
            $qb->andWhere("report.reportStatus = $searchstatus");
        }
        if ($searchtype != '') {
            $qb->andWhere("report.reportType like '%$searchtype%'");
        }
        if ($searchmonth != '') {
            $qb->andWhere("report.month = $searchmonth");
        }
        if ($searchyear != '') {
            $qb->andWhere("report.year = $searchyear");
        }
        if ($term != '') {
            $qb->andWhere(" a.anlName LIKE '$term' ");
        }
        return $qb;
    }
    /**
     * @return QueryBuilder
     */
    public function findOneByAMYT(Anlage $Anl = null, string $month = "", string $year = "", string $type = "")
    {
        $qb = $this->createQueryBuilder('a')
            ;
        if ($type != '') {
            $qb->andWhere("a.reportType like '%$type%'");
        }
        if ($month != '') {
            $qb->andWhere("a.month = $month");
        }
        if ($year != '') {
            $qb->andWhere("a.year = $year");
        }
        if ($Anl != null) {
            $qb->andWhere(" a.anlName LIKE '$Anl' ");
        }
        return $qb     ->getQuery()
            ->getResult();
    }

    //new Dashboard
    public  function  findByAnlageId(int $anlageId):array{
        return $this->createQueryBuilder('t')
            ->join('t.anlage', 'a')
            ->where('a.anlId = :anlageId')
            ->setParameter('anlageId', $anlageId)
            ->getQuery()
            ->getResult();
    }


    public function getWithSearchQueryBuilderAnlageString( ?string $anlId = '', ?string $searchmonth = '', ?string $searchyear = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('report')
            ->innerJoin('report.anlage', 'a')
            ->innerJoin('report.eigner', 'e')
            ->addSelect('a')
            ->addSelect('e')
        ;



        $qb->andWhere("report.reportType = 'string-analyse'");

        if ($anlId != '') {
            $qb->andWhere("a.anlId = $anlId");
        }

        if ($searchmonth != '') {
            $qb->andWhere("report.month = $searchmonth");
        }
        if ($searchyear != '') {
            $qb->andWhere("report.year = $searchyear");
        }

        return $qb;
    }
}
