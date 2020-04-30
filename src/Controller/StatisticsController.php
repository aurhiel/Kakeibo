<?php

namespace App\Controller;

// Entities
use App\Entity\Transaction;

// Components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistiques/{date_start}/{date_end}", name="statistics", defaults={"date_start"="current","date_end"="current"})
     */
    public function index($date_start, $date_end, Security $security, Request $request)
    {
        $user       = $security->getUser();
        $translator = $this->get('translator');

        // Force user to create at least ONE bank account !
        if(count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();
        if(count($default_bank_account->getTransactions()) < 1) {
            return $this->redirectToRoute('dashboard');
        }

        // Default values/params
        $date_start = ($date_start == 'current') ? date('Y-m-01') : $date_start;
        $date_end   = ($date_end == 'current') ? date('Y-m-t') : $date_end;
        $em       = $this->getDoctrine()->getManager();
        $r_trans  = $em->getRepository(Transaction::class);

        // Get totals
        $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $date_start, $date_end, 'incomes');
        $total_expenses = (float) $r_trans->findTotal($default_bank_account, $date_start, $date_end, 'expenses');

        // Get transactions according to current page
        $transactions = $r_trans->findByBankAccountAndDateAndPage($default_bank_account, $date_start, $date_end);
        $nb_transactions = count($transactions);

        // Redirect to a page with transactions if not current date
        if ($nb_transactions < 1 && ($date_start != date('Y-m-01') && $date_end != date('Y-m-t')))
            return $this->redirectToRoute('statistics');

        $total_incomes_by_date = self::reorderByDate($r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'incomes'));
        $total_expenses_by_date = self::reorderByDate($r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'expenses'));

        self::completeEmptyDate($total_incomes_by_date, $total_expenses_by_date);
        self::completeEmptyDate($total_expenses_by_date, $total_incomes_by_date);

        // Retrieve total incomes & expenses grouped by categories
        $total_expenses_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'category', 'expenses');

        return $this->render('statistics/index.html.twig', [
            'core_class'      => 'app-core--statistics app-core--merge-body-in-header',
            'meta'            => [ 'title' => $translator->trans('page.statistics.meta.title') ],
            'stylesheets'     => [ 'kb-dashboard.css' ],
            'scripts'         => [ 'kb-dashboard.js' ],
            'curr_date_start' => $date_start,
            'curr_date_end'   => $date_end,
            'current_bank_account'  => $default_bank_account,
            'transactions'          => $transactions,
            'nb_transactions'       => $nb_transactions,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'total_incomes_by_date'   => $total_incomes_by_date,
            'total_expenses_by_date'  => $total_expenses_by_date,
            'total_expenses_by_cats'  => $total_expenses_by_cats,
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
