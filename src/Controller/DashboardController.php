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
     * @Route("/{_locale}/dashboard", name="dashboard")
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

        $curr_month = (int) date('m');
        $curr_year  = (int) date('Y');
        $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $curr_year, $curr_month, 'incomes');
        $total_expenses = (float) $r_trans->findTotal($default_bank_account, $curr_year, $curr_month, 'expenses');

        // NOTE If there is no expenses and incomes in the current month, try
        //  to get last month expenses and incomes.
        if ($total_incomes == 0 && $total_expenses == 0) {
            $curr_month = (($curr_month - 1) < 0) ? 12 : ($curr_month - 1);
            $total_incomes  = (float) $r_trans->findTotal($default_bank_account, $curr_year, $curr_month, 'incomes');
            $total_expenses = (float) $r_trans->findTotal($default_bank_account, $curr_year, $curr_month, 'expenses');
        }

        $total_incomes_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $curr_year, $curr_month, 'category', 'incomes');
        $total_expenses_by_cats = $r_trans->findTotalGroupBy($default_bank_account, $curr_year, $curr_month, 'category', 'expenses');

        return $this->render('dashboard/index.html.twig', [
            'page_title'            => $translator->trans('page.dashboard.title'),
            'meta'                  => [ 'title' => $translator->trans('page.dashboard.title') ],
            'core_class'            => 'app-core--dashboard app-core--merge-body-in-header',
            'stylesheets'           => [ 'kb-dashboard.css' ],
            'scripts'               => [ 'kb-dashboard.js' ],
            'user'                  => $user,
            'current_bank_account'  => $default_bank_account,
            'last_transactions'     => $last_trans,
            'last_trans_amount'     => self::NB_LAST_TRANS,
            'totals_month'          => $curr_month,
            'totals_year'           => $curr_year,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'total_incomes_by_cats'   => $total_incomes_by_cats,
            'total_expenses_by_cats'  => $total_expenses_by_cats,
            'form_transaction'      => $trans_form->createView()
        ]);
    }
}
