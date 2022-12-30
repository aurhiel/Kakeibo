<?php

namespace App\Repository;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAll()
    {
        return $this->findBy([], [
            'username' => 'ASC'
        ]);
    }

    public function findAllBy(array $orderBy, $limit = null)
    {
        return $this->findBy([], $orderBy, $limit);
    }

    public function countAll()
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id) AS amount')
            ->getQuery()->getSingleScalarResult()
        ;
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function loadUserByIdentifier(string $username): ?User 
    {
        return $this->loadUserByUsername($username);
    }
}
