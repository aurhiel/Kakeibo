<?php

namespace App\Controller;

// Forms
use App\Form\TransactionType;

// Entities
use App\Entity\Transaction;

// Components
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;

class DashboardController extends Controller
{
    const NB_LAST_TRANS = 20;

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index(Security $security, Request $request)
    {
        $user       = $security->getUser();
        $translator = $this->get('translator');

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // Force user to add or import transaction(s) first
        if (count($default_bank_account->getTransactions()) < 1)
            return $this->redirectToRoute('ignition-first-transaction');

        // Build the transaction form
        $trans_entity = new Transaction($user);
        $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

        $em = $this->getDoctrine()->getManager();
        $r_trans  = $em->getRepository(Transaction::class);
        $last_trans = $r_trans->findLastByBankAccount($default_bank_account, self::NB_LAST_TRANS);

        $date_start = date('Y-m-01');
        $date_end   = date('Y-m-d');

        // Retrieve totals for incomes & expenses
        $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $date_start, 'now', 'incomes');
        $total_expenses = (float) $r_trans->findTotal($default_bank_account, $date_start, 'now', 'expenses');

        // NOTE If there is no expenses and incomes in the current month, try
        //  to get last month expenses and incomes.
        // if ($total_incomes == 0 && $total_expenses == 0) {
        //     $curr_month = (($curr_month - 1) < 0) ? 12 : ($curr_month - 1);
        //     $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $date_start, 'now', 'incomes');
        //     $total_expenses = (float) $r_trans->findTotal($default_bank_account, $date_start, 'now', 'expenses');
        // }

        // Retrieve totals grouped by categories for incomes & expenses
        // $total_incomes_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $date_start, 'now', 'category', 'incomes');
        $total_expenses_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $date_start, 'now', 'category', 'expenses');

        return $this->render('dashboard/index.html.twig', [
            'page_title'            => $translator->trans('page.dashboard.title'),
            'meta'                  => [ 'title' => $translator->trans('page.dashboard.title') ],
            'core_class'            => 'app-core--dashboard app-core--merge-body-in-header',
            'stylesheets'           => [ 'kb-dashboard.css' ],
            'scripts'               => [ 'kb-dashboard.js' ],
            'user'                  => $user,
            'current_bank_account'  => $default_bank_account,
            'dashboard_date_start'  => $date_start,
            'dashboard_date_end'    => $date_end,
            'last_transactions'     => $last_trans,
            'last_trans_amount'     => self::NB_LAST_TRANS,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            // 'total_incomes_by_cats'   => $total_incomes_by_cats,
            'total_expenses_by_cats'  => $total_expenses_by_cats,
            'form_transaction'      => $trans_form->createView()
        ]);
    }
}
