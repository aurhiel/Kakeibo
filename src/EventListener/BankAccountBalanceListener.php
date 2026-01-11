<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Transaction;
use App\Repository\BankAccountRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'recalculateBalance', entity: Transaction::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'recalculateBalance', entity: Transaction::class)]
#[AsEntityListener(event: Events::postRemove, method: 'recalculateBalance', entity: Transaction::class)]
class BankAccountBalanceListener
{
    public function __construct(
        private readonly BankAccountRepository $bankAccountRepository,
    ) {
    }

    public function recalculateBalance(Transaction $transaction, PostPersistEventArgs|PostUpdateEventArgs|PostRemoveEventArgs $event): void
    {
        $bankAccount = $transaction->getBankAccount();

        if ($bankAccount) {
            $this->bankAccountRepository->syncBalance($bankAccount->getId());
            $event->getObjectManager()->refresh($bankAccount);
        }
    }
}
