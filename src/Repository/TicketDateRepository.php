<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method TicketDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TicketDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TicketDate[]    findAll()
 * @method TicketDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketDate::class);
    }


    public function findOneById($id): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByBeginTicket($begin, $ticket): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.begin = :begin')
            ->andWhere('t.ticket = :ticket')
            ->setParameter('begin', $begin)
            ->setParameter('ticket', $ticket)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByEndTicket($end, $ticket): ?TicketDate
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.ticket = :ticket')
            ->setParameter('end', $end)
            ->setParameter('ticket', $ticket)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countByIntervalErrorPlant($begin, $end, $error, $anlage)
    {
        return $this->createQueryBuilder('t')
            ->addSelect('sum(t.intervals)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.errorType = :error')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('error', $error)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countTicketsByIntervalErrorPlant($begin, $end, $error, $anlage)
    {
        return $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.errorType = :error')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('error', $error)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countByIntervalNullPlant($begin, $end, $anlage)
    {
        return $this->createQueryBuilder('t')
            ->addSelect('sum(t.intervals)')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.dataGapEvaluation is NULL ')
            ->andWhere('t.errorType = 20')
            ->andWhere('t.alertType = 10')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAllByInterval($begin, $end, $anlage)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.begin >= :begin')
            ->andWhere('t.begin <= :end')
            ->andWhere('t.Anlage = :anlage')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * Search for all DataGap Tickets wich are outage and not evaluated data gaps (case 6)
     * means search for all Ticketdates wich are datagabs (alertType = 10) and NOT defined as comm. issue (
     *
     * @param Anlage $anlage
     * @param $begin
     * @param $end
     * @param int $department
     * @return mixed
     */
    public function findDataGapOutage(Anlage $anlage, $begin, $end, int $department): mixed
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere('t.begin BETWEEN :begin AND :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.alertType = 10')
            ->andWhere('t.dataGapEvaluation = 10');
        switch ($department){
            case 1:
                $q->andWhere('t.kpiPaDep1 = 10');
                break;
            case 2:
                $q->andWhere('t.kpiPaDep2 = 10');
                break;
            case 3:
                $q->andWhere('t.kpiPaDep3 = 10');
                break;
        }
        $q->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage);

        return $q->getQuery()->getResult();
    }

    /**
     * Search for all tiFM Cases (case 5)
     *
     * @param Anlage $anlage
     * @param $begin
     * @param $end
     * @param int $department
     * @return mixed
     */
    public function findTiFm(Anlage $anlage, $begin, $end, int $department): mixed
    {
        dump($begin, $end, $department);
        $q = $this->createQueryBuilder('t')
            ->andWhere('t.begin BETWEEN :begin AND :end')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.alertType = 20 or (t.alertType = 10 and t.dataGapEvaluation = 10)')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage);
        switch ($department){
            case 1:
                $q->andWhere('t.kpiPaDep1 = 20');
                break;
            case 2:
                $q->andWhere('t.kpiPaDep2 = 20');
                break;
            case 3:
                $q->andWhere('t.kpiPaDep3 = 20');
                break;
        };

        return $q->getQuery()->getResult();
    }
}
