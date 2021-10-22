<?php

namespace App\Repository;

use App\Entity\TransactionAuto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TransactionAuto|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransactionAuto|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransactionAuto[]    findAll()
 * @method TransactionAuto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionAutoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionAuto::class);
    }

    public function findOneByIdAndUser($id, $user)
    {
        return $this->createQueryBuilder('ta')
            ->andWhere('ta.id = :id')
            ->setParameter('id', $id)
            ->join('ta.bank_account', 'ba')
            ->andWhere('ba.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllByBankAccount($bank_account)
    {
        return $this->createQueryBuilder('ta')
            ->andWhere('ta.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->join('ta.bank_account', 'ba')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findTotal($bank_account, $spent_type = 'incomes')
    {
        $qb = $this->createQueryBuilder('ta')
            ->select('SUM(ta.amount) AS amount_sum')
            ->andWhere('ta.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // WHERE: Incomes or Expenses ?
        if ($spent_type == 'incomes') $qb->andWhere('ta.amount > 0');
        else $qb->andWhere('ta.amount < 0');

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    // /**
    //  * @return TransactionAuto[] Returns an array of TransactionAuto objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('ta.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('ta.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TransactionAuto
    {
        return $this->createQueryBuilder('t')
            ->andWhere('ta.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
