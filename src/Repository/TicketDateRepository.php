<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\TicketDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

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

    /**
     * @throws NonUniqueResultException
     */
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

    /**
     * @throws NonUniqueResultException
     */
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
            ->andWhere('t.alertType = :error')
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
            ->andWhere('t.alertType = :error')
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
     * means search for all Ticketdates wich are dataGaps (alertType = 10) and NOT defined as comm. issue (
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
            ->join('t.ticket', 'ticket')
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end and :begin >= t.begin)')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.dataGapEvaluation = 10')
            ->andWhere('ticket.ignoreTicket = false');
        switch ($department){
            case 1:
                $q->andWhere('t.kpiPaDep1 = 10');
                break;
            case 2:
                $q->andWhere('t.kpiPaDep2 = 10');
                break;
            case 3: // AssetManagemet should respect all cases as Not available
                $q->andWhere('t.kpiPaDep3 = 10 or t.kpiPaDep3 = 20 or t.kpiPaDep3 = 30');
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
        $q = $this->createQueryBuilder('t')
            ->join('t.ticket', 'ticket')
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end and :begin >= t.begin)')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('t.alertType = 20 or (t.alertType = 10 and t.dataGapEvaluation = 10)')
            ->andWhere('ticket.ignoreTicket = false')
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
            case 3: // AssetManagemet should not set any outage to ForecMajour
                $q->andWhere('t.kpiPaDep3 = 99');
                break;
            default:
                $q->andWhere('t.kpiPaDep3 = 99');
        };

        return $q->getQuery()->getResult();
    }

    /**
     * Search for Communication Issus
     *
     * @param Anlage $anlage
     * @param $begin
     * @param $end
     * @param int $department
     * @return mixed
     */
    public function findCommIssu(Anlage $anlage, $begin, $end, int $department): mixed
    {
        $q = $this->createQueryBuilder('t')
            ->join('t.ticket', 'ticket')
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end AND :begin >= t.begin)')
            ->andWhere('t.Anlage = :anlage')
            ->andWhere('(t.alertType = 10 OR t.alertType = 20)')
        ;
        if ($anlage->getTreatingDataGapsAsOutage()) {
            $q->andWhere('(t.dataGapEvaluation = 20 OR t.dataGapEvaluation = 0)');
        } else {
            $q->andWhere('t.dataGapEvaluation = 20');
        }
        $q
            ->andWhere('ticket.ignoreTicket = false')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->setParameter('anlage', $anlage);

        return $q->getQuery()->getResult();
    }

    /**
     * Search all Performance Tickets wich a relatet to PA calculation (alertType = 72)
     * @param Anlage $anlage
     * @param string|DateTime $startDate
     * @param string|DateTime $endDate
     * @param int $department
     * @param int $behaviour (10 = Skip for PA, 20 = Replace outage with TiFM for PA, )
     * @return float|int|mixed|string
     */
    public function findPerformanceTicketWithPA(Anlage $anlage, string|DateTime $startDate, string|DateTime $endDate, int $department, int $behaviour = 0): mixed
    {
        $q = $this->createQueryBuilder('t')
            ->join("t.ticket", "ticket")
            ->andWhere('ticket.scope LIKE :dep')
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end AND :begin >= t.begin)')
            ->andWhere("t.Anlage = :anlage")
            ->andWhere("ticket.kpiStatus = 10")
            ->andWhere("ticket.alertType = '72'")  // Exclude from PR/Energy = 72
            ->andWhere('ticket.ignoreTicket = false')
            ->setParameter('dep', '%'.$department.'0%')
            ->setParameter('begin', $startDate instanceof DateTime ? $startDate->format("Y-m-d H:i") : $startDate)
            ->setParameter('end', $endDate instanceof DateTime ? $endDate->format("Y-m-d H:i") : $endDate)
            ->setParameter('anlage', $anlage);

        if ($behaviour > 0) {
            $q
                ->andWhere('t.PRExcludeMethod = :behaviour')
                ->setParameter('behaviour', $behaviour);
        }

        return $q->getQuery()->getResult();
    }
    /**
     * Search for Performance Tickets
     *
     * @param Anlage $anlage
     * @param string|DateTime $startDate
     * @param string|DateTime $endDate
     * @return array
     */
    public function performanceTicketsExcludeEnergy(Anlage $anlage, string|DateTime $startDate, string|DateTime $endDate): array
    {
        $q = $this->createQueryBuilder('t')
            ->join("t.ticket", "ticket")
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end AND :begin >= t.begin)')
            ->andWhere("t.Anlage = :anlage")
            ->andWhere("ticket.kpiStatus = 10")
            ->andWhere("ticket.alertType = '72'")  // Exclude from PR/Energy = 72
            ->andWhere('ticket.ignoreTicket = false')
            ->setParameter('begin', $startDate instanceof DateTime ? $startDate->format("Y-m-d H:i") : $startDate)
            ->setParameter('end', $endDate instanceof DateTime ? $endDate->format("Y-m-d H:i") : $endDate)
            ->setParameter('anlage', $anlage);

        return $q->getQuery()->getResult();
    }
    /**
     * Search for Performance Tickets
     *
     * @param Anlage $anlage
     * @param string|DateTime $startDate
     * @param string|DateTime $endDate
     * @return array
     */
    public function performanceTickets(Anlage $anlage, string|DateTime $startDate, string|DateTime $endDate): array
    {
        $q = $this->createQueryBuilder('t')
            ->join("t.ticket", "ticket")
            ->andWhere('t.begin BETWEEN :begin AND :end OR t.end BETWEEN :begin AND :end OR (:end <= t.end AND :begin >= t.begin)')
            ->andWhere("t.Anlage = :anlage")
            ->andWhere("ticket.kpiStatus = 10")
            ->andWhere("ticket.alertType IN ('70','71','72','73','74','75')")
            ->andWhere('ticket.ignoreTicket = false')
            ->setParameter('begin', $startDate instanceof DateTime ? $startDate->format("Y-m-d H:i") : $startDate)
            ->setParameter('end', $endDate instanceof DateTime ? $endDate->format("Y-m-d H:i") : $endDate)
            ->setParameter('anlage', $anlage);

        return $q->getQuery()->getResult();
    }
}
