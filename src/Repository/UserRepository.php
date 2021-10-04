<?php


namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param string|null $term
     * @return QueryBuilder
     */
    public function getWithSearchQueryBuilder(?string $term): QueryBuilder {
        $qb = $this->createQueryBuilder('c');

        $qb->leftJoin('c.eigners','u')
            ->addSelect('u')
            ->orderBy('c.name', 'ASC');
        if ($term) {
            $qb->andWhere('c.name LIKE :term OR c.email LIKE :term OR u.id LIKE :pureterm')
                ->setParameter('term', '%' . $term . '%')
                ->setParameter('pureterm', $term)
            ;
        }

        return $qb;
    }

    /**
     * @param User $user
     * @param string $newHashedPassword
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush($user);
    }
    /**
     * @param string|null $query
     * @return array
     */
    public function findByAllMatching(string $query, int $limit = 100)
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->addSelect('u');


        return $qb->getQuery()
            ->getResult();
    }

}
