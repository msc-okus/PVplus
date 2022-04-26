<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }


    /**
     * @param string|null $searchstatus
     * @param string|null $editor
     * @param string|null $anlage
     * @param string|null $Id
     * @param string|null $Prio
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $searchstatus, ?string $editor, ?string $anlage, ?string $Id,?string $Prio ): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;
        if ($searchstatus != '' & $searchstatus!='00') $qb->andWhere("ticket.status = $searchstatus");
        if ($editor != '') $qb->andWhere("ticket.editor = '$editor'");
        if ($anlage !='') $qb->andWhere("a.anlName LIKE '$anlage'");
        if($Id != '') $qb->andWhere("ticket.id = '$Id'");
        if($Prio != '' & $Prio != '00')$qb->andWhere("ticket.priority = '$Prio'");
/*
        if ($term) {
            $qb ->andWhere('a.anlName LIKE :term')
                ->setParameter('term', '%' . $term);
        }
*/


        return $qb;

    }

    // /**
    //  * @return ticket[] Returns an array of ticket objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */


    public function findOneById($id): ?ticket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    public function findByAITNoWeather($anlage, $inverter, $time){
        $weather = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.inverter = :inv')
            ->andWhere('t.description != :weather')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('inv',$inverter)
            ->setParameter('weather',$weather)
            ->getQuery();
        return $result->getResult();
    }
    public function findByAITWeather($anlage, $time){
        $weather = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description = :weather')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)

            ->setParameter('weather',$weather)
            ->getQuery();
        return $result->getResult();
    }

    public function findLastByAITNoWeather($anlage, $inverter, $today, $yesterday){
        $weather = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end > :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.inverter = :inv')
            ->andWhere('t.description != :weather')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('inv',$inverter)
            ->setParameter('weather', $weather)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        return $result->getResult();
    }
    public function findLastByAITWeather($anlage, $today, $yesterday){
        $weather = "Error with the Data of the Weather station";
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end > :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description = :weather')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('weather',$weather)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();
        return $result->getResult();
    }

}
