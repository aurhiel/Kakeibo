<?php

namespace App\Controller;

// Entities
use App\Entity\Transaction;

// Components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistics/{year}/{month}", name="statistics", defaults={"year"="current","month"="current"})
     */
    public function index($year, $month, Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if(count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();
        if(count($default_bank_account->getTransactions()) < 1) {
            return $this->redirectToRoute('dashboard');
        }

        // Default values/params
        $year     = (int)(($year == 'current') ? date('Y') : $year);
        $month    = (int)(($month == 'current') ? date('m') : $month);
        $em       = $this->getDoctrine()->getManager();
        $r_trans  = $em->getRepository(Transaction::class);

        // Get totals
        $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $year, $month, 'incomes');
        $total_expenses = (float) $r_trans->findTotal($default_bank_account, $year, $month, 'expenses');

        // Get transactions according to current page
        $transactions = $r_trans->findByBankAccountAndDateAndPage($default_bank_account, $year, $month);
        $nb_transactions = count($transactions);

        $total_incomes_by_date = self::reorderByDate($r_trans->findTotalGroupBy($default_bank_account, $year, $month, 'date', 'incomes'));
        $total_expenses_by_date = self::reorderByDate($r_trans->findTotalGroupBy($default_bank_account, $year, $month, 'date', 'expenses'));

        self::completeEmptyDate($total_incomes_by_date, $total_expenses_by_date);
        self::completeEmptyDate($total_expenses_by_date, $total_incomes_by_date);

        return $this->render('statistics/index.html.twig', [
            'core_class'      => 'app-core--statistics app-core--merge-body-in-header',
            'stylesheets'     => [ 'kb-dashboard.css' ],
            'scripts'         => [ 'kb-dashboard.js' ],
            'curr_year'       => $year,
            'curr_month'      => $month,
            'current_bank_account'  => $default_bank_account,
            'transactions'          => $transactions,
            'nb_transactions'       => $nb_transactions,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'total_incomes_by_date'   => $total_incomes_by_date,
            'total_expenses_by_date'  => $total_expenses_by_date,
        ]);
    }

    private static function reorderByDate($transactions)
    {
        $tmp = array();
        foreach ($transactions as $trans)
            $tmp[$trans['date']->format('Y-m-d')] = $trans;

        return $tmp;
    }

    private static function completeEmptyDate(&$transactions, $completer)
    {
        foreach ($completer as $date => $comp) {
            if (!isset($transactions[$date]))
                $transactions[$date] = array('amount_sum' => 0, 'date' => $date);
        }

        ksort($transactions);
    }
}
