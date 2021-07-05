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
        return $this->findBy(array(), array(
            'username' => 'ASC'
        ));
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
}
