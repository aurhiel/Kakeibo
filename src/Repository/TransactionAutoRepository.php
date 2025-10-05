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

    public function findAllToExecuteByRepeatType(string $repeat_type)
    {
        // Check if repeat type is valid
        if (!in_array($repeat_type, TransactionAuto::RT_LIST)) {
            return TransactionAuto::ERR_UNKNOWN_RTYPE;
        } else {
            // Retrieve date type to remove (day/week/month/year)
            //  according to repeat type
            $date_last_remove = ($repeat_type == TransactionAuto::RT_DAILY) ? 'DAY' : str_replace('LY', '', $repeat_type);

            return $this->createQueryBuilder('ta')
                ->join('ta.bank_account', 'ba')
                ->andWhere('ba.is_archived = false')
                ->andWhere('ta.date_start <= CURRENT_DATE()')
                ->andWhere('ta.date_last IS NULL OR ta.date_last <= DATE_SUB(DATE(CURRENT_DATE()), 1, \'' . $date_last_remove . '\')')
                ->andWhere('ta.repeat_type = :repeat_type')
                ->setParameter('repeat_type', $repeat_type)
                ->orderBy('ta.id', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }
    }
}
