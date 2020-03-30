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
use Symfony\Component\Security\Core\Security;

class DashboardController extends Controller
{
    const NB_LAST_TRANS = 20;

    /**
     * @Route("/", name="dashboard")
     */
    public function index(Security $security, Request $request)
    {
        $user = $security->getUser();

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

        $curr_month = (int) 3;
        $curr_year  = (int) date('Y');
        $total_incomes = $r_trans->findTotalIncomes($default_bank_account, $curr_year, $curr_month);
        $total_expenses = $r_trans->findTotalExpenses($default_bank_account, $curr_year, $curr_month);

        $total_incomes = isset($total_incomes['total_incomes']) ? (float)$total_incomes['total_incomes'] : 0;
        $total_expenses = isset($total_expenses['total_expenses']) ? (float)$total_expenses['total_expenses'] : 0;

        return $this->render('dashboard/index.html.twig', [
            'page_title'            => 'Dashboard',
            'core_class'            => 'app-core--dashboard app-core--merge-body-in-header',
            'stylesheets'           => [ 'kb-dashboard.css' ],
            'scripts'               => [ 'kb-dashboard.js' ],
            'user'                  => $user,
            'current_bank_account'  => $default_bank_account,
            'last_transactions'     => $last_trans,
            'last_trans_amount'     => self::NB_LAST_TRANS,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'form_transaction'      => $trans_form->createView()
        ]);
    }
}
