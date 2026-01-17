<?php

namespace App\Command;

use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kkb:transaction:diff-finder',
    description: 'Given a CSV file try to find differences between database and the CSV',
)]
class KkbTransactionDiffFinderCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('csv-path', InputArgument::REQUIRED, 'Path to CSV to parse')
            ->addArgument('column-amount', InputArgument::REQUIRED, 'Column where amount must be retrieved')
            ->addArgument('bank-account-id', InputArgument::REQUIRED, 'The bank account to explore')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Exploration type: "missing_in_db" or "extra_in_db"', 'extra_in_db')
            ->addOption('line-start', null, InputOption::VALUE_OPTIONAL, 'Line to start at')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('csv-path');
        $mode = $input->getOption('mode');
        $bankAccountId = (int) $input->getArgument('bank-account-id');
        $columnForAmount = max((int) $input->getArgument('column-amount') - 1, 0);
        $lineStart = max((int) $input->getOption('line-start'), 0);

        if (!in_array($mode, ['missing_in_db', 'extra_in_db'])) {
            $io->error("Invalid mode. Use 'missing_in_db' or 'extra_in_db'.");
            return Command::FAILURE;
        }

        if (!file_exists($filePath)) {
            $io->error("File not found at path: $filePath");
            return Command::FAILURE;
        }

        $io->title(sprintf("Start to run command to analyze CSV & bank account content (bank_account.id: %d)", $bankAccountId));

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $io->section("Parse CSV file to simplify data from it...");

            $totalLines = count(file($filePath));
            $progressBar = new ProgressBar($output, $totalLines);
            $progressBar->start();

            $csvTransactions = [];

            $currentLine = 0;
            $minDate = $maxDate = null;
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                $currentLine++;

                if ($currentLine < $lineStart) {
                    $progressBar->advance();
                    continue;
                }

                if ($io->isVeryVerbose()) {
                    $io->text(" Line #$currentLine: " . implode(' | ', $data));
                }

                $dateFind = $this->extractDatesFromRow($data);

                if (null !== $dateFind) {
                    $csvTransactions[] = [
                        'line-number' => $currentLine,
                        'date' => $dateFind,
                        'amount' => (float) str_replace(',', '.', $data[$columnForAmount]),
                    ];

                    if (null === $minDate && null === $maxDate) {
                        $minDate = $maxDate = $dateFind;
                    }

                    $minDate = min($maxDate, $dateFind);
                    $maxDate = max($maxDate, $dateFind);
                }

                $progressBar->advance();
            }

            fclose($handle);
            $progressBar->finish();

            $io->newLine(2);
            $io->success(sprintf("Reading CSV file finished. %d rows treated on %d.", $currentLine - $lineStart + 1, $totalLines));
        } else {
            $io->error("Can't read CSV file.");

            return Command::FAILURE;
        }

        if ($mode === 'missing_in_db') {
            $this->auditMissingInDb($io, $csvTransactions, $bankAccountId);
        } else {
            $this->auditExtraInDb($io, $csvTransactions, $bankAccountId, $minDate, $maxDate);
        }

        return Command::SUCCESS;
    }

    /**
     * Mode: Check if a CSV row is MISSING in the database
     */
    private function auditMissingInDb(SymfonyStyle $io, array $csvTransactions, int $bankAccountId): void
    {
        $io->section("Searching for transactions present in CSV but missing in database");

        $notFoundInDB = 0;
        foreach ($csvTransactions as $csvTrans) {
            $date = $csvTrans['date'];
            $amount = $csvTrans['amount'];
            $currentLine = $csvTrans['line-number'];

            $transactions = $this->fetchTransactionsByDateAndAmount($bankAccountId, $date, $amount);
            if (empty($transactions)) {
                ++$notFoundInDB;
                if ($io->isVerbose()) {
                    $io->text(sprintf("Anomaly at line %d: No transaction found in database for %s at %s€", $currentLine, $date->format('d/m/Y'), $amount));
                }
            }
        }

        if ($notFoundInDB > 0) {
            $io->warning(sprintf('%d transaction(s) not found in database!', $notFoundInDB));
        } else {
            $io->success('All transactions are correctly present in database! 🎉');
        }
    }

    /**
     * Mode: Check if a DB transaction is EXTRA (not in the CSV)
     */
    private function auditExtraInDb(SymfonyStyle $io, array $csvTransactions, int $bankAccountId, \DateTime $dateStart, \DateTime $dateEnd): void
    {
        $io->section("Searching for transactions present in database but missing in CSV");

        $dbTransactions = $this->fetchTransactionsByDateStartAndDateEnd($bankAccountId, $dateStart, $dateEnd);

        if (empty($dbTransactions)) {
            $io->warning('No transaction found in database');
        }

        /**
         * @var Transaction $trans
         */
        $notFoundInCSV = 0;
        foreach ($dbTransactions as $trans) {
            $transFoundInCSV = false;
            foreach ($csvTransactions as $csvTrans) {
                if (round($trans->getAmount(), 2) === round($csvTrans['amount'], 2)
                    && $trans->getDate()->format('Y-m-d') === $csvTrans['date']->format('Y-m-d')
                ) {
                    $transFoundInCSV = true;
                    break;
                }
            }

            if (!$transFoundInCSV) {
                ++$notFoundInCSV;
                if ($io->isVerbose()) {
                    $io->text(sprintf(
                        "Anomaly: Transaction ID %d exists in database but not in CSV (date: %s, amount: %.2f)",
                        $trans->getId(),
                        $trans->getDate()->format('Y-m-d'),
                        $trans->getAmount(),
                    ));
                }
            }
        }

        if ($notFoundInCSV > 0) {
            $io->warning(sprintf('%d / %d transaction(s) in database but not found in CSV!', $notFoundInCSV, count($dbTransactions)));
        } else {
            $io->success('All transactions are correctly present in CSV and database! 🎉');
        }
    }

    private function extractDatesFromRow(array $rowData): ?\DateTime
    {
        $fullDate = null;
        $partialDateRaw = null;
        $referenceYear = null;

        // Loop through columns to find our date patterns
        foreach ($rowData as $cellValue) {
            // Match DD/MM/YYYY
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $cellValue)) {
                $fullDate = \DateTime::createFromFormat('d/m/Y', $cellValue);
                $referenceYear = $fullDate->format('Y');
            }
            // Match DD/MM (ensuring it's not a full date)
            elseif (preg_match('/\b(\d{2}\/\d{2})\b(?!\/\d{4})/', $cellValue, $matches)) {
                $partialDateRaw = $matches[1];
            }
        }

        // Comparison Logic
        if ($fullDate && $partialDateRaw) {
            // Reconstruct the partial date using the year from the full date
            $reconstructedDate = \DateTime::createFromFormat('d/m/Y', $partialDateRaw . '/' . $referenceYear);

            // Check if the partial date is more old
            if ($reconstructedDate < $fullDate) {
                // Using newLine to avoid breaking the progress bar layout
                return $reconstructedDate->setTime(0, 0);
            }
        }

        return $fullDate ? $fullDate->setTime(0, 0) : null;
    }

    private function fetchTransactionsByDateStartAndDateEnd(int $bankAccountId, \DateTime $dateStart, \DateTime $dateEnd): array
    {
        return $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->where('t.bank_account = :bankAccountId')
            ->andWhere('t.date BETWEEN :dateStart AND :dateEnd')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('dateStart', $dateStart->format('Y-m-d 00:00:00'))
            ->setParameter('dateEnd', $dateEnd->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getResult();
    }

    private function fetchTransactionsByDateAndAmount(int $bankAccountId, \DateTime $date, float $amount): array
    {
        return $this->entityManager->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->where('t.bank_account = :bankAccountId')
            ->andWhere('t.date = :date')
            ->andWhere('t.amount = :amount')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('date', $date)
            ->setParameter('amount', $amount)
            ->getQuery()
            ->getResult();
    }
}
