<?php

declare(strict_types=1);

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
 * @extends ServiceEntityRepository<BankAccount>
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAccount::class);
    }

    public function findOneByIdAndUser(int $id, User $user): ?BankAccount
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

    public function resetDefault(User $user)
    {
        return $this->createQueryBuilder('ba')
            ->update()
            ->set('ba.is_default', 'false')
            ->where('ba.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute()
        ;
    }

    public function syncBalance(int $id): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeQuery(
            'UPDATE bank_account ba SET ba.balance = (
                    SELECT COALESCE(SUM(t.amount), 0) FROM transaction t
                        WHERE t.bank_account_id = :bankAccountId
                )
            WHERE ba.id = :bankAccountId',
            ['bankAccountId' => $id],
        );
    }
}
