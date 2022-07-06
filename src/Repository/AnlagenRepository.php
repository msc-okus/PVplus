<?php
namespace App\Repository;


use App\Entity\Anlage;
use App\Entity\User;
use App\Helper\G4NTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method Anlage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anlage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anlage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnlagenRepository extends ServiceEntityRepository
{
    use G4NTrait;

    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Anlage::class);
        $this->security = $security;
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
            ->andWhere(Criteria::expr()->gt('stamp', date_create(G4NTrait::getCetTime('object')->format('Y-m-d 00:00'))))
            ->orderBy(['stamp' => 'DESC'])
            ->setMaxResults(1)
        ;
    }

    public static function lastOpenWeatherCriteria(): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->gt('stamp', G4NTrait::getCetTime('object')->format('Y-m-d 00:00')))
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
    public function findIdLike($like): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.anlId IN (:val)")
            ->orderBy('a.anlId', 'ASC')
            ->setParameter('val', $like)
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
            ->andWhere("a.eigner = :eigner")
            ->add("orderBy", ['FIELD(a.anlId, :anlage) DESC'])
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
            ->andWhere("a.eigner = :eigner")
            ->andWhere("a.anlId IN (:granted)")
            ->add("orderBy", ['FIELD(a.anlId, :anlage) DESC'])
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

    public function findAllByEigner($eigner)
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere('a.eignerId = :eigner')
            ->setParameter('eigner', $eigner)
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Anlage[]
     */
    public function findUpdateExpected(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere("a.anlHidePlant = 'No'")
            ->andWhere("a.calcPR = true")
            ->andWhere("a.excludeFromExpCalc = false OR a.excludeFromExpCalc is null")
            ->orderBy('a.anlId', 'ASC')
            ->innerJoin('a.acGroups', 'acG')
            ->innerJoin('a.groups', 'dcG')
            ->addSelect('acG')
            ->addSelect('dcG')
            ->getQuery()
            ->getResult();
    }

    /**
     * Suche alle aktiven anlagen für die ein Benutzer die Zugriffsrechte hat
     * please use in future 'findAllActivAndAllowed'
     *
     * @return Anlage[]
     * @deprecated
     */
    public function findAllActive(): array
    {
        return self::findAllActiveAndAllowed();
    }

    /**
     * Suche alle aktiven anlagen für die ein Benutzer die Zugriffsrechte hat
     *
     * @return Anlage[]
     */
    public function findAllActiveAndAllowed(): array
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
            /** @var User $user */
            $user = $this->security->getUser();
            $accesslist = $user->getAccessList();
            $qb
                ->andWhere("a.anlHidePlant = 'No'")
                ->andWhere("a.anlView = 'Yes'")
                ->andWhere("a.eigner IN (:accesslist)")
                ->setParameter('accesslist', $accesslist);
        }
        $qb
            ->orderBy('a.eigner', 'ASC')
            ->addOrderBy('a.anlName', 'ASC');

        return $qb  ->getQuery()
                    ->getResult();
    }

    /**
     * @param string|null $term
     * @return QueryBuilder
     */
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
            $qb ->andWhere('a.anlName LIKE :term OR a.anlPlz LIKE :term OR a.anlOrt LIKE :term OR eigner.firma LIKE :term' )
                ->setParameter('term', '%' . $term . '%');
        }
        return $qb  ->orderBy('eigner.firma', 'ASC')
                    ->addOrderBy('a.anlName', 'ASC');

    }

    /**
     * @param string|null $query
     * @return array
     */
    public function findByAllMatching(string $query, int $limit = 100)
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
        if (! $this->security->isGranted('ROLE_G4N')) {

            /** @var User $user */
            $user = $this->security->getUser();
            $granted = explode(',', $user->getGrantedList());

            $qb->andWhere("a.anlId IN (:granted)")
                ->setParameter('granted', $granted)
            ;
        }

        return $qb->getQuery()
            ->getResult();
    }


    /**
     * @param string|null $term
     * @param array $eigners
     * @param array $grantedPlantList
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilderOwner(?string $term, array $eigners = [], array $grantedPlantList = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.eignerId IN (:eigners) ')
            ->andWhere('c.anlId IN (:grantedPlantList)')
            ->setParameter('eigners', $eigners)
            ->setParameter('grantedPlantList', $grantedPlantList)
            ->innerJoin('c.eigner', 'a')
            ->addSelect('a')
            ->leftJoin('a.economicVarNames', 'varName')
            ->leftJoin('a.economicVarValues', 'ecoValu')
            ->leftJoin('a.settings', 'settings')
            ->addSelect('varName')
            ->addSelect('ecoValu')
            ->addSelect('settings');

        if ($term) {
            $qb ->andWhere('c.anlName LIKE :term OR c.anlPlz LIKE :term OR c.anlOrt LIKE :term OR a.firma LIKE :term' )
                ->setParameter('term', '%' . $term . '%');
        }

        return $qb  ->orderBy('a.firma', 'ASC')
            ->addOrderBy('c.anlName', 'ASC');
    }
}
