<?php

namespace App\Controller;

// Forms
// use App\Form\TransactionType;

// Entities
use App\Entity\Transaction;

// Components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class StatisticsController extends AbstractController
{
    /**
     * @Route("/statistiques/{date_start}/{date_end}", name="statistics", defaults={"date_start"="current","date_end"="current"})
     */
    public function index($date_start, $date_end, Security $security, Request $request, TranslatorInterface $translator)
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
        $is_now     = ($date_start == 'current' && $date_end == 'current');
        $date_start = ($date_start == 'current') ? date('Y-m-01') : $date_start;
        $date_end   = ($date_end == 'current') ? date('Y-m-t') : $date_end;
        $em       = $this->getDoctrine()->getManager();
        $r_trans  = $em->getRepository(Transaction::class);

        // Build the transaction form
        // $trans_entity = new Transaction();
        // $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

        // Get totals
        $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $date_start, $date_end, 'incomes');
        $total_expenses = (float) $r_trans->findTotal($default_bank_account, $date_start, $date_end, 'expenses');

        // Get transactions according to current page
        $transactions = $r_trans->findByBankAccountAndDateAndPage($default_bank_account, $date_start, $date_end);
        $nb_transactions = count($transactions);

        // Redirect to a page with transactions based on last one
        //  if current period hasn't any transactions
        if ($is_now === true && $nb_transactions < 1) {
            $last_transaction = $r_trans->findByBankAccountAndDateAndPage($default_bank_account, null, 'now', 1, 1);
            if (!empty($last_transaction) && isset($last_transaction[0])) {
                $last_transaction = $last_transaction[0];
                return $this->redirectToRoute('statistics', [
                    'date_start'  => $last_transaction->getDate()->format('Y-m-01'),
                    'date_end'    => $last_transaction->getDate()->format('Y-m-t'),
                ]);
            }
        }

        $total_incomes_by_date = self::reindexByDate($r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'incomes'));
        $total_expenses_by_date = self::reindexByDate($r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'expenses'));

        // Complete date without incomes or expense with empty transactions (= 0)
        //  according to the opposite one (incomes <> expenses)
        self::completeEmptyDate($total_incomes_by_date, $total_expenses_by_date);
        self::completeEmptyDate($total_expenses_by_date, $total_incomes_by_date);

        // Retrieve total incomes & expenses grouped by categories
        $total_incomes_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'category', 'incomes');
        $total_expenses_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'category', 'expenses');

        // Get period selected type (monthly, yearly or custom)
        //    & create previous + next links
        $period_type  = 'custom';
        $prev_link    = $next_link = null;
        $prev_date    = $next_date = null;
        if ($date_start != 'current' && $date_end != 'current') {
            // Split date start & end into few variables
            list($st_year, $st_month, $st_day)    = explode('-', $date_start);
            list($end_year, $end_month, $end_day) = explode('-', $date_end);

            // Check if start & end years are the same (= monthly or yearly)
            if ($st_year == $end_year) {
                if ($st_month == $end_month) {
                    $period_type = 'monthly';
                } else if ((int)$st_month == 1 && (int)$st_day == 1 &&
                  (int)$end_month == 12 && (int)$end_day == 31) {
                    $period_type = 'yearly';
                }

                // Create links (no navigation with custom periods TODO implement it ?)
                if ($period_type == 'monthly' || $period_type == 'yearly') {
                    $search_period = str_replace('ly', '', $period_type);
                    // Get previous + next "YEAR | MONTH" & check if has transactions
                    //    before creating links & if so create previous link
                    $prev_date_end = date('Y-m-d', strtotime('last day of -1 ' . $search_period, strtotime($date_end)));
                    $nb_prev_trans = (int)$r_trans->countAllByBankAccountAndByDate($default_bank_account, null, $prev_date_end);
                    if ($nb_prev_trans > 0) {
                        $prev_date_start = date('Y-m-d', strtotime('first day of -1 '. $search_period, strtotime($date_start)));
                        $prev_link = $this->generateUrl('statistics', [
                            'date_start'  => $prev_date_start,
                            'date_end'    => $prev_date_end
                        ]);
                        $prev_date = $prev_date_start;
                    }
                    //  & check next transactions and create link
                    $next_date_start  = date('Y-m-d', strtotime('first day of +1 '. $search_period, strtotime($date_start)));
                    $nb_next_trans    = (int)$r_trans->countAllByBankAccountAndByDate($default_bank_account, $next_date_start, null);
                    if ($nb_next_trans > 0) {
                        $next_date_end = date('Y-m-d', strtotime('last day of +1 '. $search_period, strtotime($date_end)));
                        $next_link = $this->generateUrl('statistics', [
                            'date_start'  => $next_date_start,
                            'date_end'    => $next_date_end
                        ]);
                        $next_date = $next_date_start;
                    }
                }
            }
        }

        return $this->render('statistics/index.html.twig', [
            'core_class'      => 'app-core--statistics app-core--merge-body-in-header',
            'meta'            => [ 'title' => $translator->trans('page.statistics.meta.title') ],
            // 'stylesheets'     => [ 'kb-dashboard.css' ],
            // 'scripts'         => [ 'kb-dashboard.js' ],
            'curr_date_start' => $date_start,
            'curr_date_end'   => $date_end,
            'transactions'          => $transactions,
            'nb_transactions'       => $nb_transactions,
            'trans_period_type'     => $period_type,
            'trans_prev_link'       => $prev_link,
            'trans_next_link'       => $next_link,
            'trans_prev_date'       => $prev_date,
            'trans_next_date'       => $next_date,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'total_incomes_by_date'   => $total_incomes_by_date,
            'total_incomes_by_cats'   => $total_incomes_by_cats,
            'total_expenses_by_date'  => $total_expenses_by_date,
            'total_expenses_by_cats'  => $total_expenses_by_cats,
            // 'form_transaction'        => $trans_form->createView(),
        ]);
    }

    private static function reindexByDate($transactions)
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
