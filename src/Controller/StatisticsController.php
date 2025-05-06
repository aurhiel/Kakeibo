<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\BankTransferType;
use App\Form\TransactionType;
use App\Form\CategoryType;
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
class StatisticsController extends AbstractController
{
    private User $user;
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
     * @Route("/statistiques/{date_start}/{date_end}", name="statistics", defaults={"date_start"="current","date_end"="current"})
     */
    public function index(string $date_start, string $date_end): Response
    {
        // Force user to create at least ONE bank account !
        if(count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();
        if(count($default_bank_account->getTransactions()) < 1) {
            return $this->redirectToRoute('dashboard');
        }

        // Default values/params
        $is_now = ($date_start == 'current' && $date_end == 'current');
        $date_start = ($date_start == 'current') ? date('Y-m-01') : $date_start;
        $date_end = ($date_end == 'current') ? date('Y-m-t') : $date_end;

        // Get totals
        $total_incomes = $this->transcationRepository->findTotal($default_bank_account, $date_start, $date_end, 'incomes');
        $total_expenses = $this->transcationRepository->findTotal($default_bank_account, $date_start, $date_end, 'expenses');

        // Get transactions according to current page
        $transactions = $this->transcationRepository->findByBankAccountAndDateAndPage($default_bank_account, $date_start, $date_end);
        $nb_transactions = count($transactions);

        // Redirect to a page with transactions based on last one
        //  if current period hasn't any transactions
        if ($is_now === true && $nb_transactions < 1) {
            $last_transaction = $this->transcationRepository->findByBankAccountAndDateAndPage($default_bank_account, null, 'now', 1, 1);
            if (!empty($last_transaction) && isset($last_transaction[0])) {
                $last_transaction = $last_transaction[0];
                return $this->redirectToRoute('statistics', [
                    'date_start' => $last_transaction->getDate()->format('Y-m-01'),
                    'date_end' => $last_transaction->getDate()->format('Y-m-t'),
                ]);
            }
        }

        $total_incomes_by_date = $this->reindexByDate($this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'incomes'));
        $total_expenses_by_date = $this->reindexByDate($this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'date', 'expenses'));

        // Complete date without incomes or expense with empty transactions (= 0)
        //  according to the opposite one (incomes <> expenses)
        $this->completeEmptyDate($total_incomes_by_date, $total_expenses_by_date);
        $this->completeEmptyDate($total_expenses_by_date, $total_incomes_by_date);

        // Retrieve total incomes & expenses grouped by categories
        $total_incomes_by_cats = $this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'category', 'incomes');
        $total_expenses_by_cats = $this->transcationRepository->findTotalGroupBy($default_bank_account, $date_start, $date_end, 'category', 'expenses');

        // Get period selected type (monthly, yearly or custom)
        //  & generate next and previous dates
        $period_type = $this->getTypeOfPeriod($date_start, $date_end);
        $prev_link = $next_link = null;
        $prev_date = $next_date = null;
        list($prev_date_start, $prev_date_end, $next_date_start, $next_date_end) = $this->generatePreviousAndNextDates(
            $date_start,
            $date_end,
            $period_type
        );

        // Create previous and next links
        $nb_prev_trans = $this->transcationRepository->countAllByBankAccountAndByDate($default_bank_account, null, $prev_date_end);
        if ($nb_prev_trans > 0) {
            $prev_link = $this->generateUrl('statistics', [
                'date_start' => $prev_date_start,
                'date_end' => $prev_date_end
            ]);
            $prev_date = $prev_date_start;
        }
        $nb_next_trans = $this->transcationRepository->countAllByBankAccountAndByDate($default_bank_account, $next_date_start, null);
        if ($nb_next_trans > 0) {
            $next_link = $this->generateUrl('statistics', [
                'date_start' => $next_date_start,
                'date_end' => $next_date_end
            ]);
            $next_date = $next_date_start;
        }

        return $this->render('statistics/index.html.twig', [
            'core_class'      => 'app-core--statistics app-core--merge-body-in-header',
            'meta'            => [ 'title' => $this->translator->trans('page.statistics.meta.title') ],
            'stylesheets'     => [ 'kb-statistics' ],
            'scripts'         => [ 'kb-statistics' ],
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
            'total_incomes_by_date'  => $total_incomes_by_date,
            'total_incomes_by_cats'  => $total_incomes_by_cats,
            'total_expenses_by_date' => $total_expenses_by_date,
            'total_expenses_by_cats' => $total_expenses_by_cats,
            'categories'       => $this->categoryRepository->findAllByUserId($this->user->getId()),
            'default_category' => $this->categoryRepository->findDefault(),
            'form_transaction'   => $this->createForm(TransactionType::class)->createView(),
            'form_category'      => $this->createForm(CategoryType::class)->createView(),
            'form_bank_transfer' => $this->user->hasManyBankAccounts() ? $this->createForm(BankTransferType::class)->createView() : null,
        ]);
    }

    private function reindexByDate($transactions): array
    {
        $tmp = array();
        foreach ($transactions as $trans) {
            $tmp[$trans['date']->format('Y-m-d')] = $trans;
        }

        return $tmp;
    }

    private function completeEmptyDate(&$transactions, $completer): void
    {
        foreach ($completer as $date => $comp) {
            if (!isset($transactions[$date])) {
                $transactions[$date] = array('amount_sum' => 0, 'date' => $date);
            }
        }

        ksort($transactions);
    }

    private function getTypeOfPeriod(string $dateStart, string $dateEnd): string
    {
        $periodType = 'custom';
        $dateStart = new \DateTime($dateStart);
        $dateEnd = new \DateTime($dateEnd);

        // Check if start & end years are the same (= monthly or yearly)
        if ($dateStart->format('Y') === $dateEnd->format('Y')) {
            if ((int) $dateStart->format('N') === 1
                && (int) (clone $dateStart)->modify('+6days')->format('d') === (int) $dateEnd->format('d')
            ) {
                $periodType = 'weekly';
            } else if ($dateStart->format('m') === $dateEnd->format('m')
                && (int) $dateStart->format('d') === 1
                && (int) $dateEnd->format('d') === (int) $dateEnd->format('t')
            ) {
                $periodType = 'monthly';
            } else if ((int) $dateStart->format('m') === 1 && (int) $dateStart->format('d') === 1
                && (int) $dateEnd->format('m') === 12 && (int) $dateEnd->format('d') === 31
            ) {
                $periodType = 'yearly';
            }
        }

        return $periodType;
    }

    private function generatePreviousAndNextDates(string $date_start, string $date_end, string $period_type): array
    {
        $firstOrLastDay = $period_type !== 'weekly' && $period_type !== 'custom';
        if ($period_type === 'custom') {
            $search_period = 'day';
            $modifier = (int) (new \DateTime($date_end))->diff(new \DateTime($date_start))->format('%a') + 1;
        } else {
            $search_period = str_replace('ly', '', $period_type);
            $modifier = 1;
        }

        $prev_date_start = (new \DateTime($date_start))
            ->modify(sprintf('%s-%d %s', ($firstOrLastDay ? 'first day of ' : ''), $modifier, $search_period))
            ->format('Y-m-d')
        ;

        $prev_date_end = (new \DateTime($date_end))
            ->modify(sprintf('%s-%d %s', ($firstOrLastDay ? 'last day of ' : ''), $modifier, $search_period))
            ->format('Y-m-d')
        ;

        $next_date_start = (new \DateTime($date_start))
            ->modify(sprintf('%s+%d %s', ($firstOrLastDay ? 'first day of ' : ''), $modifier, $search_period))
            ->format('Y-m-d')
        ;

        $next_date_end = (new \DateTime($date_end))
            ->modify(sprintf('%s+%d %s', ($firstOrLastDay ? 'last day of ' : ''), $modifier, $search_period))
            ->format('Y-m-d')
        ;

        return [
            $prev_date_start,
            $prev_date_end,
            $next_date_start,
            $next_date_end
        ];
    }
}
