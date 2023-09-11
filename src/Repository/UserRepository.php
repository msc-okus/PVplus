<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
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
    public function __construct(ManagerRegistry $registry, private ApiTokenRepository $apiTokenRepository, private Security $security)
    {
        parent::__construct($registry, User::class);
    }


    public function getWithSearchQueryBuilder(?string $term): QueryBuilder
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->createQueryBuilder('user');

        $qb->leftJoin('user.eigners', 'eigner')
            ->addSelect('eigner')
            ->orderBy('user.name', 'ASC');
        if (!$this->security->isGranted('ROLE_G4N')) {
            $qb->andWhere('eigner.id = :eigner')
                ->setParameter('eigner', $user->getOwner());
        }
        if ($term) {
            $qb->andWhere('user.name LIKE :term OR user.email LIKE :term OR eigner.id LIKE :pureterm OR eigner.firma LIKE :term')
                ->setParameter('term', '%'.$term.'%')
                ->setParameter('pureterm', $term)
            ;
        }

        return $qb;
    }

    /**
     * @param User $user
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush($user);
    }

    /**
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function findByAllMatching(string $query, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->addSelect('u');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param string $apiToken
     * @return User|null
     */
    public function findByApiToken(string $apiToken): ?User
    {
        return $this->apiTokenRepository->findOneBy(['token' => $apiToken])?->getUser();
    }
}
