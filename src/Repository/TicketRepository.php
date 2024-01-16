<?php
namespace App\Repository;

use App\Entity\Anlage;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{


    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
        private readonly AnlagenRepository $anlRepo)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Build query with all options, including 'has user rights to see'
     * OLD VERSION.
     *
     * @deprecated
     */
    public function getWithSearchQueryBuilder(?string $status, ?string $editor, ?string $anlage, ?string $id, ?string $prio, ?string $inverter): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $granted = explode(',', $user->getGrantedList());

        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;
        if (!$this->security->isGranted('ROLE_G4N')) {
            $qb
                ->andWhere('a.anlId IN (:plantList)')
                ->setParameter('plantList', $granted)
            ;
        }
        if ($status != '' && $status != '00') {
            $qb->andWhere("ticket.status = $status");
        }
        if ($editor != '') {
            $qb->andWhere("ticket.editor = '$editor'");
        }
        if ($anlage != '') {
            $qb->andWhere("a.anlName LIKE '$anlage'");
        }
        if ($id != '') {
            $qb->andWhere("ticket.id = '$id'");
        }
        if ($prio != '' && $prio != '00') {
            $qb->andWhere("ticket.priority = '$prio'");
        }

        return $qb;
    }

    public function countByProof(){

        $result = $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.needsProof = true')
            ->getQuery()
        ;
        return $result->getResult()[0][1];
    }

    public function countByProofAM(){

        $result = $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.ProofAM = true')
            ->getQuery()
        ;
        return $result->getResult()[0][1];
    }
    public function countByProofEPC(){

        $result = $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.needsProofEPC = true')
            ->getQuery()
        ;
        return $result->getResult()[0][1];
    }

    public function countByProofG4N(){

        $result = $this->createQueryBuilder('t')
            ->addSelect('count(t.id)')
            ->andWhere('t.needsProofg4n = true')
            ->getQuery()
        ;
        return $result->getResult()[0][1];
    }
    /**
     * Build query with all options, including 'has user rights to see'.
     *
     * @param Anlage|null $anlage
     * @param string|null $editor
     * @param string|null $id
     * @param string|null $prio
     * @param string|null $status
     * @param string|null $category
     * @param string|null $type
     * @param string|null $inverter
     * @param int $prooftam
     * @param int $proofepc
     * @param int $proofam
     * @param int $proofg4n
     * @param string $sort
     * @param string $direction
     * @param bool $ignore
     * @param string $TicketName
     * @param int $kpistatus
     * @param string $begin
     * @param string $end
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilderNew(?Anlage $anlage, ?string $editor, ?string $id, ?string $prio, ?string $status, ?string $category, ?string $type, ?string $inverter, int $prooftam = 0,int $proofepc = 0, int $proofam = 0, int $proofg4n = 0, string $sort = "", string $direction = "", bool $ignore = false, string $TicketName = "", int $kpistatus = 0, string $begin = "", string $end = ""): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $granted =  $this->anlRepo->findAllActiveAndAllowed();

        $qb = $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.anlage', 'a')
            ->addSelect('a')
        ;
        if (!$this->security->isGranted('ROLE_G4N')) {

                $qb->andWhere('a.anlId IN (:plantList)')
                    ->setParameter('plantList', $granted);
        }
        if ($anlage != '') {
            $qb->andWhere("ticket.anlage = '$anlage'");
        }
        if ($editor != '') {
            $qb->andWhere("ticket.editor = '$editor'");
        }
        if ((int) $id > 0) {
            $qb->andWhere("ticket.id = $id");
        }
        if ($inverter != '') {
            $qb->andWhere("ticket.inverter LIKE '$inverter,%' or ticket.inverter LIKE '% $inverter,%' or ticket.inverter = '$inverter' or ticket.inverter LIKE '%, $inverter'");
        }
        if ((int) $prio > 0) {
            $qb->andWhere("ticket.priority = $prio");
        }
        if ((int) $status > 0) {
            $qb->andWhere("ticket.status = $status");
        }
        if ((int) $type > 0) {
            $qb->andWhere("ticket.errorType = $type");
        } // SFOR, EFOR, OMC

        if ((int) $category == 7){
            $qb->andWhere("ticket.alertType >= 70");
            $qb->andWhere("ticket.alertType < 80");
        }
        else if ((int) $category == 9){
            $qb->andWhere("ticket.alertType > 90");
            $qb->andWhere("ticket.alertType < 100");
        }
        else if ((int) $category > 0) {
            $qb->andWhere("ticket.alertType = $category");
        }
        else {
            $qb->andWhere("ticket.alertType < 90 or ticket.alertType >= 100");
      
        }
        if ($prooftam == 1){
            $qb->andWhere("ticket.needsProof = 1");
        }
        if ($proofepc == 1){
            $qb->andWhere("ticket.needsProofEPC = 1");
        }
        if ($proofam == 1){
            $qb->andWhere("ticket.ProofAM  = 1");
        }
        if($proofg4n == 1){
            $qb->andWhere("ticket.needsProofg4n  = 1");
        }
        if ($kpistatus != 0){
            $qb->andWhere("ticket.kpiStatus = $kpistatus");
        }
        if ($TicketName !== "") $qb->andWhere("ticket.TicketName = '$TicketName'");
        if ($ignore) $qb->andWhere("ticket.ignoreTicket = true");
        else $qb->andWhere("ticket.ignoreTicket = false");

        if ($sort !== "") $qb->addOrderBy($sort, $direction);
            $qb->addOrderBy("ticket.id", "ASC"); // second order by ID
        if ($begin != "" && $end == ""){

            $qb->andWhere("ticket.begin LIKE '$begin%'");
        }
        else if ($begin == "" && $end != ""){
            $qb->andWhere("ticket.begin LIKE '$end%'");
        }
        else{
            if ($begin != "" ){
                $qb->andWhere("ticket.end > '$begin'");
            }
            if ($end != ""){
                $qb->andWhere("ticket.end < '$end'");
            }
        }


        return $qb;
    }

    public function findForSafeDelete($anlage, $begin, $end = null)
    {
        if ($end != null)
            $result = $this->createQueryBuilder('t')
                ->andWhere('t.anlage = :anl')
                ->andWhere('t.begin >= :begin')
                ->andWhere('t.begin <= :end')
                ->andWhere("t.editor = 'Alert system'")
                ->setParameter('anl', $anlage)
                ->setParameter('begin', $begin)
                ->setParameter('end', $end)
                ->getQuery()
            ;
        else
            $result = $this->createQueryBuilder('t')
                ->andWhere('t.anlage = :anl')
                ->andWhere('t.begin >= :begin')
                ->andWhere("t.editor = 'Alert system'")
                ->setParameter('anl', $anlage)
                ->setParameter('begin', $begin)
                ->getQuery()
            ;

        return $result->getResult();
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

    public function findMultipleByBeginErrorAnlage($anlage, $time, $errorCategory)
    {
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.begin = :begin')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->andWhere('t.alertType = :cat')
            ->setParameter('begin', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('cat', $errorCategory)
            ->setParameter('description', $description)
            ->getQuery();

        return $result->getResult();
    }
    public function findByAnlageTimeYesterday($anlage, $yesterday, $time, $errorCategory)
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end >= :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.alertType = :error')
            ->setParameter('today', $time)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('error', $errorCategory)
            ->orderBy('t.end', 'DESC')
            ->getQuery();

        return $result->getResult();
    }
    public function findByAnlageTime($anlage, $time, $errorCategory)
    {
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->andWhere('t.alertType = :cat')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('cat', $errorCategory)
            ->setParameter('description', $description)
            ->getQuery();
        return $result->getResult();
    }
    public function findAllByTime($anlage, $time){
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('description', $description)
            ->getQuery();
        return $result->getResult();
    }
    public function findAllYesterday($anlage, $today, $yesterday){
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end >= :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->andWhere('t.openTicket = true')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('description', $description)
            ->orderBy('t.end', 'DESC')
            ->getQuery();

        return $result->getResult();
    }

    public function findLastByAnlageTime($anlage, $today, $yesterday, $errorCategory)
    {
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end >= :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.alertType = :error')
            ->andWhere('t.description != :description')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('error', $errorCategory)
            ->setParameter('description', $description)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $result->getResult();
    }
    public function findAllLastByAnlageTime($anlage, $today, $yesterday){
        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end >= :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('description', $description)
            ->orderBy('t.end', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $result->getResult();
    }
    public function findLastByAnlageInverterTime($anlage, $today, $yesterday, $errorCategory, $inverter)
    {
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end < :today')
            ->andWhere('t.end >= :yesterday')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.alertType = :error')
            ->andWhere('t.inverter = :inverter')
            ->setParameter('today', $today)
            ->setParameter('yesterday', $yesterday)
            ->setParameter('anl', $anlage)
            ->setParameter('error', $errorCategory)
            ->setParameter('inverter', $inverter)
            ->orderBy('t.end', 'DESC')
            ->getQuery();

        return $result->getResult();
    }

    public function findByAnlageInverterTime($anlage, $time, $errorCategory, $inverter)
    {

        $description = 'Error with the Data of the Weather station';
        $result = $this->createQueryBuilder('t')
            ->andWhere('t.end = :end')
            ->andWhere('t.anlage = :anl')
            ->andWhere('t.description != :description')
            ->andWhere('t.alertType = :cat')
            ->andWhere('t.inverter = :inverter')
            ->setParameter('end', $time)
            ->setParameter('anl', $anlage)
            ->setParameter('cat', $errorCategory)
            ->setParameter('inverter', $inverter)
            ->setParameter('description', $description)
            ->getQuery();

        return $result->getResult();
    }

}