<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    public function countAllByBankAccountAndByDate($bank_account, $date_start = null, $date_end = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS nb_transactions')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // WHERE: transaction's date start
        if (!is_null($date_start))
            $qb->andWhere('t.date >= :date_start')
                ->setParameter('date_start', $date_start);
        // WHERE: transaction's date end
        if (!is_null($date_end)) {
            if ($date_end == 'now') {
                $qb->andWhere('t.date <= CURRENT_DATE()');
            } else {
                $qb->andWhere('t.date <= :date_end')
                    ->setParameter('date_end', $date_end);
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findByBankAccountAndDateAndPage($bank_account, $date_start = null, $date_end = null, $page = null, $max_results = 25)
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // WHERE: transaction's date start
        if (!is_null($date_start))
            $qb->andWhere('t.date >= :date_start')
                ->setParameter('date_start', $date_start);
        // WHERE: transaction's date end
        if (!is_null($date_end)) {
            if ($date_end == 'now') {
                $qb->andWhere('t.date <= CURRENT_DATE()');
            } else {
                $qb->andWhere('t.date <= :date_end')
                    ->setParameter('date_end', $date_end);
            }
        }

        // PAGINATOR
        if (!is_null($page)) {
            $qb->setFirstResult(($page - 1) * $max_results)
                ->setMaxResults($max_results);

            $pag = new Paginator($qb);

            return $pag->getQuery()->getResult();
        } else {
            return $qb->getQuery()->getResult();
        }
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

    public function findTotal($bank_account, $date_start = null, $date_end = null, $spent_type = 'incomes')
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS amount_sum')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // WHERE: Incomes or Expenses ?
        if ($spent_type == 'incomes') $qb->andWhere('t.amount > 0');
        else $qb->andWhere('t.amount < 0');

        // WHERE: transaction's date start
        if (!is_null($date_start))
            $qb->andWhere('t.date >= :date_start')
                ->setParameter('date_start', $date_start);
        // WHERE: transaction's date end
        if (!is_null($date_end)) {
            if ($date_end == 'now') {
                $qb->andWhere('t.date <= CURRENT_DATE()');
            } else {
                $qb->andWhere('t.date <= :date_end')
                    ->setParameter('date_end', $date_end);
            }
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findTotalGroupBy($bank_account, $date_start = null, $date_end = null, $group_by = 'category', $spent_type = 'incomes')
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) AS amount_sum')
            ->andWhere('t.bank_account = :bank_account')
            ->setParameter('bank_account', $bank_account);

        // WHERE: Incomes or Expenses ?
        if ($spent_type == 'incomes') $qb->andWhere('t.amount > 0');
        else $qb->andWhere('t.amount < 0');

        // WHERE: transaction's date start
        if (!is_null($date_start))
            $qb->andWhere('t.date >= :date_start')
                ->setParameter('date_start', $date_start);
        // WHERE: transaction's date end
        if (!is_null($date_end)) {
            if ($date_end == 'now') {
                $qb->andWhere('t.date <= CURRENT_DATE()');
            } else {
                $qb->andWhere('t.date <= :date_end')
                    ->setParameter('date_end', $date_end);
            }
        }

        // GROUP BY: Category
        if (!is_null($group_by) && $group_by == 'category') {
            $qb->join('t.category', 'c')
                ->addSelect('c.id, c.label, c.color, c.icon')
                ->groupBy('t.category');
        }

        // GROUP BY: Date
        if (!is_null($group_by) && $group_by == 'date') {
            $qb->addSelect('t.date')
                ->groupBy('t.date');
        }

        // return result
        return $qb->getQuery()
            ->getResult();
    }
}
