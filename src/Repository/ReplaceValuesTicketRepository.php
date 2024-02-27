<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\ReplaceValuesTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<ReplaceValuesTicket>
 *
 * @method ReplaceValuesTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReplaceValuesTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReplaceValuesTicket[]    findAll()
 * @method ReplaceValuesTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReplaceValuesTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReplaceValuesTicket::class);
    }

    /**
     * @throws NonUniqueResultException|InvalidArgumentException
     */
    public function getSum(Anlage $anlage, \DateTime $startDate, \DateTime $endDate)
    {
        $pNom = $anlage->getPnom();
        $pNomEast = $anlage->getPowerEast();
        $pNomWest = $anlage->getPowerWest();
        $inverterPowerDc = $anlage->getPnomInverterArray();
        // depending on $department generate correct SQL code to calculate
        if ($anlage->getIsOstWestAnlage()){
            $sqlTheoPowerPart = ",
                SUM(t.irrEast * $pNomEast * IFELSE(t.irrEast > ".$anlage->getThreshold2PA3().", t.pa, 1)) + 
                SUM(t.irrWest * $pNomWest * IFELSE(t.irrWest > ".$anlage->getThreshold2PA3().", t.pa, 1)) / 4000 as theo_power_pa3,
                SUM(t.irrEast * $pNomEast * IFELSE(t.irrEast > ".$anlage->getThreshold2PA2().", t.pa, 1)) + 
                SUM(t.irrWest * $pNomWest * IFELSE(t.irrWest > ".$anlage->getThreshold2PA2().", t.pa, 1)) / 4000 as theo_power_pa2,
                SUM(t.irrEast * $pNomEast * IFELSE(t.irrEast > ".$anlage->getThreshold2PA1().", t.pa, 1)) + 
                SUM(t.irrWest * $pNomWest * IFELSE(t.irrWest > ".$anlage->getThreshold2PA1().", t.pa, 1)) / 4000 as theo_power_pa1,
                SUM(t.irrEast * $pNomEast * IFELSE(t.irrEast > ".$anlage->getThreshold2PA0().", t.pa, 1)) + 
                SUM(t.irrWest * $pNomWest * IFELSE(t.irrWest > ".$anlage->getThreshold2PA0().", t.pa, 1)) / 4000 as theo_power_pa0";
        } else {
            $sqlTheoPowerPart = ", 
                SUM(t.irrModule * $pNom * IFELSE(t.irrModule > " . $anlage->getThreshold2PA3() . ", t.pa, 1)) / 4000 as theoPowerPA3,
                SUM(t.irrModule * $pNom * IFELSE(t.irrModule > " . $anlage->getThreshold2PA2() . ", t.pa, 1)) / 4000 as theoPowerPA2,
                SUM(t.irrModule * $pNom * IFELSE(t.irrModule > " . $anlage->getThreshold2PA1() . ", t.pa, 1)) / 4000 as theoPowerPA1,
                SUM(t.irrModule * $pNom * IFELSE(t.irrModule > " . $anlage->getThreshold2PA0() . ", t.pa, 1)) / 4000 as theoPowerPA0";
        }
        $sqlTheoPowerPart = ", 
                SUM(t.irrModule * $pNom * t.pa) / 4000 as theoPowerPA3,
                SUM(t.irrModule * $pNom * t.pa) / 4000 as theoPowerPA2,
                SUM(t.irrModule * $pNom * t.pa) / 4000 as theoPowerPA1,
                SUM(t.irrModule * $pNom * t.pa) / 4000 as theoPowerPA0";

        $q = $this->createQueryBuilder('t')
            ->andWhere("t.anlage = :anlage AND t.stamp >= :begin AND t.stamp <= :end")
            ->setParameter('anlage', $anlage)
            ->setParameter('begin', $startDate->format("Y-m-d H:i"))
            ->setParameter('end', $endDate->format("Y-m-d H:i"))
            ->select("SUM(t.irrHorizontal) as irrHorizontal, 
                            SUM(t.irrModule) as irrModul,
                            SUM(t.irrEast) as irrEast,
                            SUM(t.irrWest) as irrWest,
                            SUM(t.power) as power $sqlTheoPowerPart");


        return $q->getQuery()->getOneOrNullResult();

    }


    public function getIrrArray(Anlage $anlage, \DateTime $startDate, \DateTime $endDate): array
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere("t.anlage = :anlage AND t.stamp >= :begin AND t.stamp < :end")
            ->setParameter('anlage', $anlage)
            ->setParameter('begin', $startDate->format("Y-m-d H:i"))
            ->setParameter('end', $endDate->format("Y-m-d H:i"))
            ->select("DATE_FORMAT(t.stamp, '%Y-%m-%d %H:%i:%s') as stamp,
                            t.irrHorizontal as irrHorizontal, 
                            t.irrModule as irrModul,
                            t.irrEast as irrEast,
                            t.irrWest as irrWest");

        return $q->getQuery()->getArrayResult();
    }

    public function save(ReplaceValuesTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReplaceValuesTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
