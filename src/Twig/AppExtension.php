<?php

namespace App\Twig;

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
            new TwigFilter('anonymize', [$this, 'anonymize']),
            new TwigFilter('balance',   [$this, 'bankAccountBalance']),
            new TwigFilter('intval',    fn ($value) => intval($value)),
        ];
    }

    public function anonymize($string, $anonymizeCharacter = '*', $nbCharacVisible = 1)
    {
        $str_array = str_split($string);

        // Anonymize all string if it's smaller than the amount
        //  of characters visible
        if (strlen($string) <= ($nbCharacVisible * 2))
            $nbCharacVisible = 0;

        // Replace string characters
        foreach ($str_array as $k => $character) {
            if (($k + 1) > $nbCharacVisible && $k < (count($str_array) - $nbCharacVisible))
              $str_array[$k] = $anonymizeCharacter;
        }

        return implode('', $str_array);
    }

    public function bankAccountBalance($bankAccount): float
    {
        return $this->transactionRepository->findBalance($bankAccount);
    }
}
