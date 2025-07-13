<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransactionManager
{
    public function __construct(
        private BankAccountRepository $bankAccountRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function handleBankTransfer(
        User $user,
        int $bankAccountIdFrom,
        int $bankAccountIdTo,
        int $categoryId,
        float $amount,
        \DateTime $date,
        string $label,
        ?string $details = null,
        ?int $idTransactionFrom = null
    ): array {
        // dump("handleBankTransfer( {$user->getId()}, $bankAccountIdFrom, $bankAccountIdTo, ... )");

        $category = $this->categoryRepository->findOneByIdAndUser($categoryId, $user);
        $bankAccountFrom = $this->bankAccountRepository->findOneByIdAndUser($bankAccountIdFrom, $user);

        // TODO: Use $idTransactionFrom to retreive "from" & "to"

        $transactionFrom = (new Transaction())
            ->setBankAccount($bankAccountFrom)
            ->setCategory($category)
            ->setLabel($label)
            ->setDate($date)
            ->setAmount($amount * -1)
            ->setDetails($details)
        ;

        $bankAccountTo = $this->bankAccountRepository->findOneByIdAndUser($bankAccountIdTo, $user);
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

        return [
            $transactionFrom,
            $transactionTo,
        ];
    }
}
