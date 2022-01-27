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

    /*
    public function findOneBySomeField($value): ?ticket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
