<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Entities
use App\Entity\Transaction;
use App\Entity\TransactionAuto;

class KkbAutomatonExecuteCommand extends Command
{
    private $container;
    private $doctrine;

    protected static $defaultName = 'kkb:automaton:execute';
    protected static $defaultDescription = 'Parse users recurrent transactions and add transaction if needed.';

    public function __construct(ContainerInterface $container, ?string $name = null)
    {
        parent::__construct($name);

        $this->container = $container;
        $this->doctrine = $this->container->get('doctrine');
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'repeat_type',
                InputArgument::REQUIRED,
                'The recurrent transations repeat type to execute (' . implode(', ', TransactionAuto::RT_LIST) . ').')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve inputs arguments
        $repeat_type = $input->getArgument('repeat_type');
        // Set some vars.
        $error          = null;
        $io             = new SymfonyStyle($input, $output);
        $em             = $this->doctrine->getManager();
        $r_trans_auto   = $em->getRepository(TransactionAuto::class);
        $trans_to_exec  = $r_trans_auto->findAllToExecuteByRepeatType($repeat_type);

        // Add title
        $io->title('Automaton Command (Repeat type: ' . $repeat_type . ')');

        // Check if recurrent transactions to do
        if (!empty($trans_to_exec) && $trans_to_exec !== TransactionAuto::ERR_UNKNOWN_RTYPE) {
            // Add section text & init progressbar
            $io->section('Adding new transactions:');
            $io->progressStart(count($trans_to_exec));

            // Get now DateTime to assign later
            $now = new \DateTime();

            // Loop on recurrent transactions
            foreach ($trans_to_exec as $key => $trans_auto) {
                // Create new transaction & fill data according to
                //  recurrent transaction values
                $trans = new Transaction();
                $trans->fillWithTransAuto($trans_auto)
                  ->setDate($now);

                // Update recurrent transaction date last execution
                $trans_auto->setDateLast($now);

                // Persist new transaction & update recurrent transaction
                $em->persist($trans);
                $em->persist($trans_auto);

                // Sleep 1 second to get a fake loading TODO remove ?
                // sleep(1);

                // Update progressbar
                $io->progressAdvance();
            }

            $io->progressFinish();

            // Save new transactions in database & update recurrents transactions
            try {
                $em->flush();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            // Display error or success message
            if (!is_null($error)) {
                $io->error('Something goes wrong during the adding of new transactions ! (' . $error . ')');
            } else {
                $io->success('New transactions correctly added.');
            }
        } else {
            if ($trans_to_exec === TransactionAuto::ERR_UNKNOWN_RTYPE) {
                $io->error('Unknown recurrent transations repeat type (Given type: ' . $repeat_type . ', valid types: ' . implode(', ', TransactionAuto::RT_LIST) . ').');
            } else {
                $io->section('No recurrent transactions to execute.');
            }
        }

        return is_null($error) ? Command::SUCCESS : Command::FAILURE;
    }
}
