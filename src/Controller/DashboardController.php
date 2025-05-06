<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\BankTransferType;
use App\Form\CategoryType;
use App\Form\TransactionType;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class DashboardController extends AbstractController
{
    const NB_LAST_TRANS = 20;

    private ?User $user;
    private TranslatorInterface $translator;
    private TransactionRepository $transcationRepository;
    private CategoryRepository $categoryRepository;

    public function __construct(
        Security $security,
        TranslatorInterface $translator,
        TransactionRepository $transcationRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->user = $security->getUser();
        $this->translator = $translator;
        $this->transcationRepository = $transcationRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index(): Response
    {
        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();

        // Force user to add or import transaction(s) first
        if (count($default_bank_account->getTransactions()) < 1)
            return $this->redirectToRoute('ignition-first-transaction');

        $last_trans = $this->transcationRepository->findLastByBankAccount($default_bank_account, self::NB_LAST_TRANS);
        $date_start = date('Y-m-01');
        $date_end = date('Y-m-d');
        // Retrieve totals for incomes & expenses
        $total_incomes = $this->transcationRepository->findTotal($default_bank_account, $date_start, 'now', 'incomes');
        $total_expenses = $this->transcationRepository->findTotal($default_bank_account, $date_start, 'now', 'expenses');

        // NOTE If there is no expenses and incomes in the current month, try
        //  to get last month expenses and incomes.
        // if ($total_incomes == 0 && $total_expenses == 0) {
        //     $curr_month = (($curr_month - 1) < 0) ? 12 : ($curr_month - 1);
        //     $total_incomes  = $this->transcationRepository->findTotal($default_bank_account, $date_start, 'now', 'incomes');
        //     $total_expenses = $this->transcationRepository->findTotal($default_bank_account, $date_start, 'now', 'expenses');
        // }

        // Retrieve totals grouped by categories for incomes & expenses
        $total_incomes_by_cats = $this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, 'now', 'category', 'incomes');
        $total_expenses_by_cats = $this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, 'now', 'category', 'expenses');

        return $this->render('dashboard/index.html.twig', [
            'page_title'            => $this->translator->trans('page.dashboard.title'),
            'meta'                  => [ 'title' => $this->translator->trans('page.dashboard.title') ],
            'core_class'            => 'app-core--dashboard app-core--merge-body-in-header',
            // 'stylesheets'           => [ 'kb-dashboard.css' ],
            // 'scripts'               => [ 'kb-dashboard.js' ],
            'dashboard_date_start'  => $date_start,
            'dashboard_date_end'    => $date_end,
            'last_transactions'     => $last_trans,
            'last_trans_amount'     => self::NB_LAST_TRANS,
            'total_incomes'         => $total_incomes,
            'total_expenses'        => $total_expenses,
            'total_incomes_by_cats'  => $total_incomes_by_cats,
            'total_expenses_by_cats' => $total_expenses_by_cats,
            'categories'       => $this->categoryRepository->findAllByUserId($this->user->getId()),
            'default_category' => $this->categoryRepository->findDefault(),
            'form_transaction'   => $this->createForm(TransactionType::class)->createView(),
            'form_category'      => $this->createForm(CategoryType::class)->createView(),
            'form_bank_transfer' => $this->user->hasManyBankAccounts() ? $this->createForm(BankTransferType::class)->createView() : null,
        ]);
    }
}
