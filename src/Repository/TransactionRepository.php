<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    public function findOneByIdAndUser($id, $user)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->join('t.bank_account', 'ba')
            ->andWhere('ba.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countAllByBankAccount($bank_account)
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS nb_transactions')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByBankAccountAndByPage($bank_account, $page, $max_results = 25)
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // PAGINATOR
        $qb->setFirstResult(($page - 1) * $max_results)
            ->setMaxResults($max_results);

        $pag = new Paginator($qb);

        return $pag->getQuery()->getResult();
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findLastByBankAccount($bank_account, $max_results = 10)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->andWhere('t.date <= CURRENT_DATE()')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults((int) $max_results)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findTotal($bank_account, $year = null, $month = null, $spent_type = 'incomes')
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS amount_sum')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->andWhere('t.date <= CURRENT_DATE()');

        // WHERE: Incomes or Expenses ?
        if ($spent_type == 'incomes') $qb->andWhere('t.amount > 0');
        else $qb->andWhere('t.amount < 0');

        // WHERE: date
        if (!is_null($month) && !is_null($year)) {
            $qb->andWhere('MONTH(t.date) = :month AND YEAR(t.date) = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function findTotalGroupBy($bank_account, $year = null, $month = null, $group_by = 'category', $spent_type = 'incomes')
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS amount_sum')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account)
            ->andWhere('t.date <= CURRENT_DATE()');

        // WHERE: Incomes or Expenses ?
        if ($spent_type == 'incomes') $qb->andWhere('t.amount > 0');
        else $qb->andWhere('t.amount < 0');

        // WHERE: Date
        if (!is_null($month) && !is_null($year)) {
            $qb->andWhere('MONTH(t.date) = :month AND YEAR(t.date) = :year')
                ->setParameter('month', $month)
                ->setParameter('year', $year);
        }

        // GROUP BY: Category
        if (!is_null($group_by) && $group_by == 'category') {
            $qb->join('t.category', 'c')
                ->addSelect('c.id, c.label, c.color, c.icon')
                ->groupBy('t.category');
        }

        // return result
        return $qb->getQuery()
            ->getResult();
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
