<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Ticket::class);
        $this->security = $security;
    }


    /**
     * Build query with all options, including 'has user rights to see'
     * OLD VERSION
     *
     * @deprecated
     *
     * @param string|null $status
     * @param string|null $editor
     * @param string|null $anlage
     * @param string|null $id
     * @param string|null $prio
     * @param string|null $inverter
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $status, ?string $editor, ?string $anlage, ?string $id, ?string $prio, ?string $inverter ): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $granted = explode(',', $user->getGrantedList());

        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;
        if (! $this->security->isGranted('ROLE_G4N')) {
            $qb
                ->andWhere('a.anlId IN (:plantList)')
                ->setParameter('plantList', $granted)
            ;
        }
        if ($status != '' && $status!='00') $qb->andWhere("ticket.status = $status");
        if ($editor != '')                  $qb->andWhere("ticket.editor = '$editor'");
        if ($anlage !='')                   $qb->andWhere("a.anlName LIKE '$anlage'");
        if ($id != '')                      $qb->andWhere("ticket.id = '$id'");
        if ($prio != '' && $prio != '00')   $qb->andWhere("ticket.priority = '$prio'");

        return $qb;
    }

    /**
     * Build query with all options, including 'has user rights to see'
     *
     * @param string|null $anlage
     * @param string|null $editor
     * @param string|null $id
     * @param string|null $prio
     * @param string|null $status
     * @param string|null $category
     * @param string|null $type
     * @param string|null $inverter
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilderNew(?string $anlage, ?string $editor, ?string $id, ?string $prio, ?string $status, ?string $category, ?string $type, ?string $inverter): QueryBuilder
    {

        /** @var User $user */
        $user = $this->security->getUser();
        $granted = explode(',', $user->getGrantedList());

        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;
        if (! $this->security->isGranted('ROLE_G4N')) {
            $qb
                ->andWhere('a.anlId IN (:plantList)')
                ->setParameter('plantList', $granted)
            ;
        }

        if ($anlage != '')      $qb->andWhere("a.anlName = $anlage");
        if ($editor != '')      $qb->andWhere("ticket.editor = $editor");
        if ((int)$id > 0)       $qb->andWhere("ticket.id = $id");

        if ($inverter != '') {
            $qb ->andWhere('ticket.inverter LIKE :inverter')
                ->setParameter('inverter', '%' . $inverter .'%');
        }
        if ((int)$prio > 0)     $qb->andWhere("ticket.priority = $prio");
        if ((int)$status > 0)   $qb->andWhere("ticket.status = $status");
        if ((int)$type > 0)     $qb->andWhere("ticket.errorType = $type"); // SFOR, EFOR, OMC
        if ((int)$category > 0) $qb->andWhere("ticket.alertType = $category");

        return $qb;
    }


    public function findOneById($id): ?ticket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /* todo: please explain 'AIT' */
    public function findByAITNoWeather($anlage, $inverter, $time){
        $description = "Error with the Data of the Weather station ??? really weather";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.inverter = :inv')
            ->andWhere('t.description != :description')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('inv',$inverter)
            ->setParameter('description',$description)
            ->getQuery();

        return $result->getResult();
    }

    /* todo: please explain 'AIT' */
    public function findLastByAITNoWeather($anlage, $inverter, $today, $yesterday)
    {
        $description = "Error with the Data of the Weather station ??? really weather";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end > :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.inverter = :inv')
            ->andWhere('t.description != :description')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('inv',$inverter)
            ->setParameter('description', $description)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $result->getResult();
    }

    /* todo: please explain 'AIT' */
    public function findByAITWeather($anlage, $time)
    {
        $description = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description = :description')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('description',$description)
            ->getQuery();

        return $result->getResult();
    }

    /* todo: please explain 'AIT' */
    public function findLastByAITWeather($anlage, $today, $yesterday)
    {
        $description = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end > :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description = :description')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('description',$description)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $result->getResult();
    }

}
