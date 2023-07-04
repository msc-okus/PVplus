<?php

namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\ReplaceValuesTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

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
     * @throws NonUniqueResultException
     */
    public function getSum(Anlage $anlage, \DateTime $startDate, \DateTime $endDate)
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere("t.anlage = :anlage AND t.stamp >= :begin AND t.stamp < :end")
            ->setParameter('anlage', $anlage)
            ->setParameter('begin', $startDate->format("Y-m-d H:i"))
            ->setParameter('end', $endDate->format("Y-m-d H:i"))
            ->select("SUM(t.irrHorizontal) as irrHorizontal, 
                            SUM(t.irrModule) as irrModul,
                            SUM(t.irrEast) as irrEast,
                            SUM(t.irrWest) as irrWest,
                            SUM(t.power) as power" );

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
