<?php

namespace App\Repository;

use App\Entity\BankAccount;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankAccount[]    findAll()
 * @method BankAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    public function findOneByIdAndUser(int $id, User $user)
    {
        return $this->createQueryBuilder('ba')
            ->where('ba.id = :id')
            ->andWhere('ba.user = :user')
            ->setParameters([
                'id' => $id,
                'user' => $user,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
