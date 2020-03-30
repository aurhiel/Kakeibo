<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findLastByBankAccount($bank_account, $max_results = 10)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults((int) $max_results)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTotalExpenses($bank_account, $year = null, $month = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS total_expenses')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->andWhere('t.amount < 0');

        if (!is_null($month) && !is_null($year)) {
            $qb->andWhere('MONTH(t.date) = :month AND YEAR(t.date) = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTotalIncomes($bank_account, $year = null, $month = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS total_incomes')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->andWhere('t.amount > 0');

        if (!is_null($month) && !is_null($year)) {
            $qb->andWhere('MONTH(t.date) = :month AND YEAR(t.date) = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
