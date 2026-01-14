<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BankAccount;
use App\Entity\Category;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

class TransactionManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function handleBankTransfer(
        BankAccount $bankAccountFrom,
        BankAccount $bankAccountTo,
        Category $category,
        float $amount,
        \DateTime $date,
        string $label,
        ?string $details = null,
    ): Transaction {
        $transactionFrom = (new Transaction())
            ->setBankAccount($bankAccountFrom)
            ->setCategory($category)
            ->setLabel($label)
            ->setDate($date)
            ->setAmount($amount * -1)
            ->setDetails($details)
        ;

        $transactionTo = (new Transaction())
            ->setBankAccount($bankAccountTo)
            ->setCategory($category)
            ->setLabel($label)
            ->setDate($date)
            ->setAmount($amount)
            ->setDetails($details)
        ;

        $transactionTo->setBankTransferLinkedTransaction($transactionFrom);
        $transactionFrom->setBankTransferLinkedTransaction($transactionTo);

        $this->entityManager->persist($transactionFrom);
        $this->entityManager->persist($transactionTo);

        $this->entityManager->flush();

        return $transactionFrom;
    }
}
