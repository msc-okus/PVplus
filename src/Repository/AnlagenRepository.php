<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\User;
use App\Helper\G4NTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Ticket;
use App\Entity\NotificationInfo;

/**
 * @method Anlage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anlage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anlage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlagenRepository extends ServiceEntityRepository
{
    use G4NTrait;

    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Anlage::class);
    }


    public static function selectLegendType($type): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq('type', $type))
            ->orderBy(['row' => 'ASC'])
        ;
    }

    public static function case5ByDateCriteria($date): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gte('stampFrom', date_create($date)->format('Y-m-d 00:00')))
            ->andWhere(Criteria::expr()->lte('stampFrom', date_create($date)->format('Y-m-d 23:59')))
            ->orderBy(['inverter' => 'ASC'])
        ;
    }

    public static function case6ByDateCriteria($date): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gte('stampFrom', date_create($date)->format('Y-m-d 00:00')))
            ->andWhere(Criteria::expr()->lte('stampFrom', date_create($date)->format('Y-m-d 23:59')))
            ->orderBy(['inverter' => 'ASC'])
        ;
    }

    public static function lastAnlagenStatusCriteria(): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gt('stamp', date_create(self::getCetTime('object')->format('Y-m-d 00:00'))))
            ->orderBy(['stamp' => 'DESC'])
            ->setMaxResults(1)
        ;
    }

    public static function sensorsInUse(): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq('useToCalc', true))
            ;
    }

    public static function lastOpenWeatherCriteria(): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gt('stamp', self::getCetTime('object')->format('Y-m-d 00:00')))
            ->orderBy(['stamp' => 'DESC'])
            ->setMaxResults(1)
        ;
    }

    public static function anlagenStatusOrderedCriteria(): Criteria
    {
        return Criteria::create()
            ->orderBy(['anlagenStatus' => 'ASC'])
        ;
    }

    public static function lastAnlagenPRCriteria(): Criteria
    {
        return Criteria::create()
            ->orderBy(['stamp' => 'DESC'])
            ->setMaxResults(1)
        ;
    }

    public static function yesterdayAnlagenPRCriteria(): Criteria
    {
        $date = new \DateTime('-1day');

        return Criteria::create()
            ->andWhere(Criteria::expr()->lte('stamp', $date))
            ->orderBy(['stamp' => 'DESC'])
            ->setMaxResults(1)
        ;
    }

    public static function oneMonthPvSystCriteria($month): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->eq('month', $month))
        ;
    }

    /**
     * @return Anlage []
     */
    public function findIdLike($id): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlId = :id')
            ->orderBy('a.anlId', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param $id
     * @return Anlage|null
     * @throws NonUniqueResultException
     */
    public function findOneByIdAndJoin($id): ?Anlage
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlId = :id')
            ->leftJoin('a.groups', 'groups')
            ->leftJoin('groups.modules', 'moduls')
            ->leftJoin('a.acGroups', 'ac_groups')
            ->leftJoin('a.settings', 'settings')
            ->join('a.eigner', 'eigner')
            ->addSelect('groups')
            ->addSelect('moduls')
            ->addSelect('ac_groups')
            ->addSelect('settings')
            ->addSelect('eigner')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    public function findOneByName($name){
        return $this->createQueryBuilder('a')
            ->andWhere('a.anlName LIKE :name')
            ->setParameter('name', '%'.$name.'%')
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function findAlertSystemActive(bool $active){
        return $this->createQueryBuilder('a')
            ->andWhere('a.ActivateTicketSystem = (:val)')
            ->setParameter('val', $active)
            ->getQuery()
            ->getResult()
            ;
    }
    public function findAlertSystemActiveByEigner(bool $active, string $eignerId){
        return $this->createQueryBuilder('a')
            ->andWhere('a.ActivateTicketSystem = (:val)')
            ->setParameter('val', $active)
            ->andWhere('a.eignerId = (:owner)')
            ->setParameter('owner', $eignerId)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findInternalAlertSystemActive(bool $active){
        return $this->createQueryBuilder('a')
            ->andWhere('a.internalTicketSystem = (:val)')
            ->setParameter('val', $active)
            ->getQuery()
            ->getResult()
            ;
    }
    /**
     * @return Anlage[]
     */
    public function findByEignerActive($eignerId, $anlageId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings');
        if ($this->security->isGranted('ROLE_G4N')) {
            $qb
                ->andWhere("a.anlHidePlant = 'No'");
        } else {
            $qb
                ->andWhere("a.anlHidePlant = 'No'")
                ->andWhere("a.anlView = 'Yes'")
            ;
        }
        $qb
            ->andWhere('a.eigner = :eigner')
            ->add('orderBy', ['FIELD(a.anlId, :anlage) DESC'])
            ->addOrderBy('a.anlName')
            ->setParameter('eigner', $eignerId)
            ->setParameter('anlage', $anlageId)
        ;

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Anlage[]
     */
    public function findGrantedActive($eignerId, $anlageId, $granted): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings');
        if ($this->security->isGranted('ROLE_G4N')) {
            $qb
                ->andWhere("a.anlHidePlant = 'No'");
        } else {
            $qb
                ->andWhere("a.anlHidePlant = 'No'")
                ->andWhere("a.anlView = 'Yes'")
            ;
        }
        $qb
            ->andWhere('a.eigner = :eigner')
            ->andWhere('a.anlId IN (:granted)')
            ->add('orderBy', ['FIELD(a.anlId, :anlage) DESC'])
            ->addOrderBy('a.anlName')
            ->setParameter('eigner', $eignerId)
            ->setParameter('granted', $granted)
            ->setParameter('anlage', $anlageId)
        ;

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Anlage[]
     *
     * @deprecated
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.anlHidePlant = 'No'")
            ->orderBy('a.eigner', 'ASC')
            ->addOrderBy('a.anlName', 'ASC')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->getQuery()
            ->getResult();
    }

    public function findAllAnlage(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.anlName','a.anlId')
            ->andWhere("a.anlHidePlant = 'No'")
            ->orderBy('a.eigner', 'ASC')
            ->addOrderBy('a.anlName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllIDByEigner($eigner): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.anlName','a.anlId','a.country')
            ->andWhere('a.eignerId = :eigner')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere("a.anlView = 'Yes'")
            ->orderBy('a.country')
            ->addOrderBy('a.anlName')
            ->setParameter('eigner', $eigner)
            ->getQuery()
            ->getResult();
    }
    public function findSymfonyImportByEigner($eigner): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.anlName','a.anlId','a.country')
            ->leftJoin('a.settings', 'settings')
            ->where('settings.symfonyImport = true')
            ->andWhere('a.eignerId = :eigner')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere("a.anlView = 'Yes'")
            ->orderBy('a.country')
            ->addOrderBy('a.anlName')
            ->setParameter('eigner', $eigner)
            ->getQuery()
            ->getResult();
    }

    public function findAllIDByUseDayahead(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.useDayaheadForecast = 1')
            ->orderBy('a.anlId', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findAllAnlageByUser($userid): array
    {
        $query = $this->createQueryBuilder('a');
        $query->select('a.anlId')
            ->leftJoin('a.eigner','e')->addSelect('e.id')
            ->where('a.eigner = :creator')
            ->setParameter('creator', $userid);
        return $query->getQuery()->getResult();
    }

    /**
     * @return Anlage[]
     */
    public function findUpdateExpected(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere('a.calcPR = true')
            ->andWhere('a.excludeFromExpCalc = false OR a.excludeFromExpCalc is null')
            ->orderBy('a.anlId', 'ASC')
            ->innerJoin('a.acGroups', 'acG')
            ->innerJoin('a.groups', 'dcG')
            ->addSelect('acG')
            ->addSelect('dcG')
            ->orderBy('a.anlId', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * Suche alle aktiven anlagen für die ein Benutzer die Zugriffsrechte hat.
     *
     * @return Anlage[]
     */
    public function findAllActiveAndAllowed(): array
    {
        $qb = self::querBuilderFindAllActiveAndAllowed();

        return $qb->getQuery()->getResult();
    }

    /**
     * Suche alle aktiven Anlagen für die ein Benutzer die Zugriffsrechte hat und die mit Symfony Importiert werden
     *
     * @return Anlage[]
     */
    public function findAllSymfonyImport(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere('settings.symfonyImport = true');

        return $qb->getQuery()->getResult();
    }

    public function querBuilderFindAllActiveAndAllowed(): QueryBuilder
    {

        $qb = $this->createQueryBuilder('a')
            #->leftJoin('a.economicVarNames', 'varName')
            #->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->leftJoin('a.eigner', 'eigner')
            #->addSelect('varName')
            #->addSelect('ecoValu')
            ->addSelect('settings')
            ->andWhere("a.anlHidePlant = 'No'");

        if ($this->security->isGranted('ROLE_G4N')) {
            #$qb->andWhere("a.anlHidePlant = 'No'");
        } elseif ($this->security->isGranted('ROLE_OPERATIONS_G4N')){
            // Wenn Benutzer die 'Operations' Rolle hat
            $qb->andWhere('eigner.operations = 1');
        } else {
            /** @var User $user */
            $user = $this->security->getUser();
            $granted = $user->getGrantedArray();
            $qb->andWhere("a.anlView = 'Yes'");
            if ($user->getAllPlants()) {
                $qb
                    ->andWhere('a.eignerId = :eigner')
                    ->setParameter('eigner', $user->getEigners()[0]);
            } else {
                $qb
                    ->andWhere("a.anlId IN (:granted)")
                    ->setParameter('granted', $granted);
            }
        }
        $qb
            ->orderBy('a.anlName', 'ASC') //a.eigner
        ;

        dump($qb->getQuery()->getSQL());
        return $qb;
    }

    public function getWithSearchQueryBuilder(?string $term): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.eigner', 'eigner')
            ->addSelect('eigner')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
        ;

        if ($term) {
            $qb->andWhere('a.anlName LIKE :term OR a.anlPlz LIKE :term OR a.anlOrt LIKE :term OR eigner.firma LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb->orderBy('eigner.firma', 'ASC')
                    ->addOrderBy('a.anlName', 'ASC');
    }

    /**
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function findByAllMatching(string $query, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->andWhere('a.anlName LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->addSelect('a');

        // Wenn Benutzer kein G4N Rolle hat
        if (!$this->security->isGranted('ROLE_G4N')) {
            /** @var User $user */
            $user = $this->security->getUser();
            $granted = explode(',', $user->getGrantedList());

            $qb->andWhere('a.anlId IN (:granted)')
                ->setParameter('granted', $granted);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getWithSearchQueryBuilderOwner(?string $term, array $eigners = [], array $grantedPlantList = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.eignerId IN (:eigners) ')
            ->andWhere('a.anlId IN (:grantedPlantList)')
            ->setParameter('eigners', $eigners)
            ->setParameter('grantedPlantList', $grantedPlantList)
            ->innerJoin('a.eigner', 'eigner')
            ->addSelect('eigner')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings');

        if ($term) {
            $qb->andWhere('a.anlName LIKE :term OR a.anlPlz LIKE :term OR a.anlOrt LIKE :term OR eigner.firma LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb->orderBy('eigner.firma', 'ASC')
            ->addOrderBy('a.anlName', 'ASC');
    }

    public function getOwner(array $eigners = [], array $grantedPlantList = []): QueryBuilder
    {

        if ($this->security->isGranted('ROLE_G4N')) {
            $qb = $this->createQueryBuilder('a')
                ->innerJoin('a.eigner', 'eigner')
                ->addSelect('eigner')
                ->leftJoin('a.economicVarNames', 'varName')
                ->leftJoin('a.economicVarValues', 'ecoValu')
                ->leftJoin('a.settings', 'settings')
                ->addSelect('varName')
                ->addSelect('ecoValu')
                ->addSelect('settings')
                ->addOrderBy('a.anlName', 'ASC');
            return $qb;
        }


        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.eignerId IN (:eigners) ')
            ->andWhere('a.anlId IN (:grantedPlantList)')
            ->setParameter('eigners', $eigners)
            ->setParameter('grantedPlantList', $grantedPlantList)
            ->innerJoin('a.eigner', 'eigner')
            ->addSelect('eigner')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->addOrderBy('a.anlName', 'ASC');

        return $qb;
    }

    public function findPlantsForDashboardForUserWithGrantedList(array $grantedArray, $user): array
    {
        $qb = $this->createQueryBuilder('anlage')
            ->innerJoin('anlage.eigner', 'e')
            ->innerJoin('e.user', 'u')
            ->addSelect('e')
            ->addSelect('e.firma AS firma')
            ->leftJoin('anlage.tickets', 't') // General left join for tickets
            ->addSelect('COUNT(t.id) AS tickets_total')
                ->addSelect('SUM(CASE WHEN t.status = 10 THEN 1 ELSE 0 END) AS tickets_status_10')
                ->addSelect('(SELECT GROUP_CONCAT(t10.id) FROM App\Entity\Ticket t10 WHERE t10.anlage = anlage AND t10.status = 10) AS tickets_status_10_ids')
                ->addSelect('SUM(CASE WHEN t.status = 30 THEN 1 ELSE 0 END) AS tickets_status_30')
                ->addSelect('(SELECT GROUP_CONCAT(t30.id) FROM App\Entity\Ticket t30 WHERE t30.anlage = anlage AND t30.status = 30) AS tickets_status_30_ids')
                ->addSelect('SUM(CASE WHEN t.status = 40 THEN 1 ELSE 0 END) AS tickets_status_40')
                ->addSelect('(SELECT GROUP_CONCAT(t40.id) FROM App\Entity\Ticket t40 WHERE t40.anlage = anlage AND t40.status = 40) AS tickets_status_40_ids')
                ->addSelect('SUM(CASE WHEN t.status = 90 AND t.createdAt >= :dateLimit90 THEN 1 ELSE 0 END) AS  last_7_days_tickets_status_90') // Date limit applied for status 90
                ->addSelect('(SELECT GROUP_CONCAT(t90.id) FROM App\Entity\Ticket t90 WHERE t90.anlage = anlage AND t90.status = 90 AND t90.createdAt >= :dateLimit90) AS  last_7_days_tickets_status_90_ids') // Date limit on IDs for status 90
                ->addSelect('SUM(CASE 
                      WHEN t.status = 10 THEN 1 
                      WHEN t.status = 30 THEN 1 
                      WHEN t.status = 40 THEN 1 
                      WHEN t.status = 90 AND t.createdAt >= :dateLimit90 THEN 1 
                      ELSE 0 
                  END) AS tickets_status_sum') // Summing specific statuses into one total
                ->setParameter('dateLimit90', new \DateTime('-7 days')) // Setting the specific date limit for status 90
                ->addSelect('(SELECT GROUP_CONCAT(t_all.id) FROM App\Entity\Ticket t_all WHERE t_all.anlage = anlage) AS all_ticket_ids')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());

        // Apply the granted list filter
        if (!empty($grantedArray)) {
            $qb->andWhere('anlage.anlId IN (:grantedArray)')
                ->setParameter('grantedArray', $grantedArray);
        }

        // Apply the criteria for active plants
        $qb->andWhere('anlage.anlHidePlant = :anlHidePlant')
            ->setParameter('anlHidePlant', 'No')
            ->andWhere('anlage.anlView = :anlView')
            ->setParameter('anlView', 'Yes');

        // Group the results by anlage_id to ensure aggregation functions correctly
        $qb->groupBy('anlage.anlId');

        // Subquery to get the latest NotificationInfo details for each Ticket
        $ticketsQb = $this->getEntityManager()->createQueryBuilder()
            ->select('t.id AS ticket_id, 
                  ni.id AS notification_info_id, 
                  ni.closeDate AS notification_info_close_date, 
                  ni.answerDate AS notification_info_answer_date, 
                  ni.Date AS notification_info_date')
            ->from(NotificationInfo::class, 'ni')
            ->leftJoin('ni.Ticket', 't')
            ->groupBy('t.id')
            ->orderBy('ni.Date', 'DESC')
            ->addGroupBy('ni.id')
            ->having('ni.id IS NOT NULL');

        $ticketsResult = $ticketsQb->getQuery()->getResult();
        $ticketsMap = [];
        foreach ($ticketsResult as $row) {
            if ($row['notification_info_close_date']) {
                $ticketsMap[$row['ticket_id']] = 'closed';
            } elseif ($row['notification_info_answer_date']) {
                $ticketsMap[$row['ticket_id']] = 'work in process';
            } elseif ($row['notification_info_date']) {
                $ticketsMap[$row['ticket_id']] = 'new';
            }
        }


        $results = $qb->getQuery()->getResult();

        foreach ($results as &$result) {
            $ticketIds = explode(',', $result['all_ticket_ids']);
            $notificationInfoDetails = [];
            foreach ($ticketIds as $ticketId) {
                if (isset($ticketsMap[$ticketId])) {
                    $notificationInfoDetails[$ticketId] = $ticketsMap[$ticketId];
                }
            }
            $result['mro'] = $notificationInfoDetails;
            unset($result['all_ticket_ids']);
        }

        return $results;
    }


    public function findPlantsForDashboard(): array
    {
        // Main query for fetching Anlage details
        $qb = $this->createQueryBuilder('anlage')
            ->innerJoin('anlage.eigner', 'eigner')
            ->addSelect('eigner')
            ->addSelect('eigner.firma AS firma')
            ->leftJoin('anlage.economicVarNames', 'varName')
            ->leftJoin('anlage.economicVarValues', 'ecoValu')
            ->leftJoin('anlage.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->where('eigner.active = 1')
            ->andWhere('anlage.anlHidePlant = :anlHidePlant')
            ->setParameter('anlHidePlant', 'No')
            ->orderBy('eigner.firma', 'ASC')
            ->addOrderBy('anlage.anlName', 'ASC');

        // Add joins and selections for tickets
        $qb->leftJoin('anlage.tickets', 't') // General left join for tickets
        ->addSelect('COUNT(t.id) AS tickets_total')
            ->addSelect('SUM(CASE WHEN t.status = 10 THEN 1 ELSE 0 END) AS tickets_status_10')
            ->addSelect('(SELECT GROUP_CONCAT(t10.id) FROM App\Entity\Ticket t10 WHERE t10.anlage = anlage AND t10.status = 10) AS tickets_status_10_ids')
            ->addSelect('SUM(CASE WHEN t.status = 30 THEN 1 ELSE 0 END) AS tickets_status_30')
            ->addSelect('(SELECT GROUP_CONCAT(t30.id) FROM App\Entity\Ticket t30 WHERE t30.anlage = anlage AND t30.status = 30) AS tickets_status_30_ids')
            ->addSelect('SUM(CASE WHEN t.status = 40 THEN 1 ELSE 0 END) AS tickets_status_40')
            ->addSelect('(SELECT GROUP_CONCAT(t40.id) FROM App\Entity\Ticket t40 WHERE t40.anlage = anlage AND t40.status = 40) AS tickets_status_40_ids')
            ->addSelect('SUM(CASE WHEN t.status = 90 AND t.createdAt >= :dateLimit90 THEN 1 ELSE 0 END) AS  last_7_days_tickets_status_90') // Date limit applied for status 90
            ->addSelect('(SELECT GROUP_CONCAT(t90.id) FROM App\Entity\Ticket t90 WHERE t90.anlage = anlage AND t90.status = 90 AND t90.createdAt >= :dateLimit90) AS  last_7_days_tickets_status_90_ids') // Date limit on IDs for status 90
            ->addSelect('SUM(CASE 
                      WHEN t.status = 10 THEN 1 
                      WHEN t.status = 30 THEN 1 
                      WHEN t.status = 40 THEN 1 
                      WHEN t.status = 90 AND t.createdAt >= :dateLimit90 THEN 1 
                      ELSE 0 
                  END) AS tickets_status_sum') // Summing specific statuses into one total
            ->setParameter('dateLimit90', new \DateTime('-7 days')) // Setting the specific date limit for status 90
            ->addSelect('(SELECT GROUP_CONCAT(t_all.id) FROM App\Entity\Ticket t_all WHERE t_all.anlage = anlage) AS all_ticket_ids')
            ->groupBy('anlage.anlId');


        // Subquery to get the latest NotificationInfo details for each Ticket
        $ticketsQb = $this->getEntityManager()->createQueryBuilder()
            ->select('t.id AS ticket_id, 
                  ni.id AS notification_info_id, 
                  ni.closeDate AS notification_info_close_date, 
                  ni.answerDate AS notification_info_answer_date, 
                  ni.Date AS notification_info_date')
            ->from(NotificationInfo::class, 'ni')
            ->leftJoin('ni.Ticket', 't')
            ->groupBy('t.id')
            ->orderBy('ni.Date', 'DESC')
            ->addGroupBy('ni.id')
            ->having('ni.id IS NOT NULL');

        $ticketsResult = $ticketsQb->getQuery()->getResult();
        $ticketsMap = [];
        foreach ($ticketsResult as $row) {
            if ($row['notification_info_close_date']) {
                $ticketsMap[$row['ticket_id']] = 'closed';
            } elseif ($row['notification_info_answer_date']) {
                $ticketsMap[$row['ticket_id']] = 'work';
            } elseif ($row['notification_info_date']) {
                $ticketsMap[$row['ticket_id']] = 'new';
            }
        }


        $results = $qb->getQuery()->getResult();

        foreach ($results as &$result) {
            $ticketIds = explode(',', $result['all_ticket_ids']);
            $notificationInfoDetails = [];
            foreach ($ticketIds as $ticketId) {
                if (isset($ticketsMap[$ticketId])) {
                    $notificationInfoDetails[$ticketId] = $ticketsMap[$ticketId];
                }
            }
            $result['mro'] = $notificationInfoDetails;
            unset($result['all_ticket_ids']);
        }

        return $results;
    }









}

