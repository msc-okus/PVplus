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
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term,?string $searchstatus,?string $editor,?string $anlage): QueryBuilder
    {
        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;

        // Wenn Benutzer kein G4N Rolle hat
        /*
        if (! $this->security->isGranted('ROLE_G4N')) {

            $user = $this->security->getUser();
            $accessList = $user->getAccessList();
            $qb->andWhere('ticket.editor in (:accessList)')
                ->setParameter('accessList', $accessList);
        }
        */
        // schließe Archiv und falsche Reports aus
        // muss noch via Backend auswählbar gemacht werden

        if ($searchstatus != '' & $searchstatus!='00') {
             $qb->andWhere("ticket.status = $searchstatus");
        }

        if ($editor != '') {
            $qb->andWhere("ticket.editor = '$editor'");
        }

        if ($anlage !='') {
            $qb->andWhere("a.anlName = '$anlage'");
        }
/*
        if ($term) {
            $qb ->andWhere('a.anlName LIKE :term')
                ->setParameter('term', '%' . $term);
        }
*/


        return $qb;

    }
    // /**
    //  * @return Ticket[] Returns an array of Ticket objects
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
    public function findOneBySomeField($value): ?Ticket
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
