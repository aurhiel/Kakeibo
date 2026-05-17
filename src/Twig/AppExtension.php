<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\BankAccount;
use App\Enum\BalanceType;
use App\Repository\TransactionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function __construct(private TransactionRepository $transactionRepository)
    {
    }

    public function getFilters()
    {
        return [
            new TwigFilter('anonymize', $this->anonymize(...)),
            new TwigFilter('balance', [$this, 'bankAccountBalance']),
            new TwigFilter('incoming_transactions', [$this, 'bankAccountIncomingTransactions']),
            new TwigFilter('intval', static fn ($value): int => intval($value)),
        ];
    }

    public function anonymize($string, $anonymizeCharacter = '*', $nbCharacVisible = 1): string
    {
        $str_array = str_split((string) $string);

        // Anonymize all string if it's smaller than the amount of characters visible
        if (strlen((string) $string) <= ($nbCharacVisible * 2)) {
            $nbCharacVisible = 0;
        }

        // Replace string characters
        foreach (array_keys($str_array) as $k) {
            if (($k + 1) > $nbCharacVisible && $k < (count($str_array) - $nbCharacVisible)) {
                $str_array[$k] = $anonymizeCharacter;
            }
        }

        return implode('', $str_array);
    }

    public function bankAccountBalance(BankAccount $bankAccount, string $balanceType = 'actual'): float
    {
        $balanceType = BalanceType::from($balanceType);
        $balance = $this->transactionRepository->findBalance($bankAccount, $balanceType);

        if (BalanceType::Incoming === $balanceType) {
            $balance += $bankAccount->sumIncomingTransactionAutos();
        }

        return $balance;
    }

    public function bankAccountIncomingTransactions(BankAccount $bankAccount): array
    {
        $transactions = $bankAccount->getIncomingTransactionAutos();

        return array_merge(
            $transactions->toArray(),
            $this->transactionRepository->findByBankAccountAndDateAndPage(
                $bankAccount,
                (new \DateTimeImmutable('tomorrow'))->format('Y-m-d'),
                (new \DateTimeImmutable('+14 days'))->format('Y-m-d'),
            ),
        );
    }
}
