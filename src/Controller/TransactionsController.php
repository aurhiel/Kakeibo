<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\BankTransferType;
use App\Form\TransactionType;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use App\Service\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
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
class TransactionsController extends AbstractController
{
    const NB_TRANSAC_BY_PAGE = 50;

    private User $user;
    private EntityManagerInterface $entityManager;
    private TransactionRepository $transactionRepository;
    private CategoryRepository $categoryRepository;
    private TranslatorInterface $translator;
    private TransactionManager $transactionManager;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        TransactionRepository $transactionRepository,
        CategoryRepository $categoryRepository,
        TranslatorInterface $translator,
        TransactionManager $transactionManager
    ) {
        $this->user = $security->getUser();
        $this->entityManager = $entityManager;
        $this->transactionRepository = $transactionRepository;
        $this->categoryRepository = $categoryRepository;
        $this->translator = $translator;
        $this->transactionManager = $transactionManager;
    }

    /**
     * @Route("/transactions/manage", name="transaction_manage")
     */
    public function manage(Request $request): Response
    {
        $id_trans = (int) $request->request->get('id');
        $is_edit = (!empty($id_trans) && $id_trans > 0); // Edit transaction ?

        if($is_edit) {
            // Get transaction to edit with id AND user (for security)
            $trans_entity = $this->transactionRepository->findOneByIdAndUser($id_trans, $this->user);
            $old_trans_json = self::format_json($trans_entity);
            $message_status_ok = 'Modificiation de la transaction effectuée.';
            $message_status_nok = 'Un problème est survenu lors de la modification de la transaction';
        } else {
            // New Entity
            $trans_entity = new Transaction();
            $message_status_ok = 'Ajout de la transaction effectuée.';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la transaction';
        }

        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();
        // Data to return/display
        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $message_status_nok
        ];

        // 1) Build the form
        $trans_form = $this->createForm(TransactionType::class, $trans_entity);

        // 2) Handle the submit (will only happen on POST)
        $trans_form->handleRequest($request);

        if ($trans_form->isSubmitted() && $trans_form->isValid()) {
            // 3) Add some data to entity
            $trans_entity->setBankAccount($default_bank_account);

            // 4) Save by persisting entity
            $this->entityManager->persist($trans_entity);

            // 5) Try or clear
            try {
                // Flush OK !
                $this->entityManager->flush();

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => $message_status_ok,
                    'entity' => self::format_json($trans_entity),
                    'default_bank_account' => self::format_json_bank_account($default_bank_account)
                ];

                // Force old entity values into entity data (useful for JS edit)
                if ($is_edit && isset($old_trans_json))
                    $return_data['entity']['old'] = $old_trans_json;
            } catch (\Exception $e) {
                $this->entityManager->clear();
                $return_data['exception'] = $e->getMessage();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            // Redirect to dashboard
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * @Route("/transactions/get/{id}", name="transaction_get")
     */
    public function retrieve(int $id, Request $request): Response
    {
        $data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $this->translator->trans('form.errors.generic')
        ];

        // Retrieve transaction with id AND user (for security)
        $trans = $this->transactionRepository->findOneByIdAndUser($id, $this->user);

        if (!is_null($trans)) {
            $data = [
                'query_status' => 1,
                'slug_status' => 'success',
                'message_status' => 'Success !',
                'entity' => self::format_json($trans)
            ];
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($data);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * @Route("/transactions/delete/{id}", name="transaction_delete")
     */
    public function delete(int $id, Request $request): Response
    {
        // Retrieve transaction with id AND user (for security)
        $trans = $this->transactionRepository->findOneByIdAndUser($id, $this->user);
        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => 'Un problème est survenu lors de la suppression de la transaction'
        ];

        if(null !== $trans) {
            $trans_deleted = $trans;
            $trans_deleted_json = self::format_json($trans_deleted);

            // Remove entity
            $this->entityManager->remove($trans);

            // Try to save (flush) or clear entity remove
            try {
                // Flush OK !
                $this->entityManager->flush();
                // Retrieve user's default bank account
                $default_bank_account = $this->user->getDefaultBankAccount();

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => 'Suppression de la transaction effectuée.',
                    // Data
                    'entity' => $trans_deleted_json,
                    'default_bank_account' => self::format_json_bank_account($default_bank_account)
                ];
                $return_data['entity']['amount'] = 0;
                $return_data['entity']['old'] = $trans_deleted_json;
            } catch (\Exception $e) {
                $this->entityManager->clear();
                $return_data['exception'] = $e->getMessage();
            }
        } else {
            $return_data['message_status'] = 'Aucune transaction n\'existe pour cet ID';
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            // Redirect to previous page (= referer)
            return $this->redirect($request->headers->get('referer'));
        }
    }

    /**
     * @Route("/transactions/bank-transfer", name="transactions_bank_transfer")
     */
    public function bankTransfer(Request $request): Response
    {
        $id_trans = (int) $request->request->get('id');
        $is_edit = (!empty($id_trans) && $id_trans > 0);

        if($is_edit) {
            // Get transaction to edit with id AND user (for security)
            $entity = $this->transactionRepository->findOneByIdAndUser($id_trans, $this->user);
            $old_entity_json = self::format_json($entity);
            $message_status_ok = 'Modificiation du transfert effectuée.';
            $message_status_nok = 'Un problème est survenu lors de la modification du transfert';
        } else {
            // New Entity
            $entity = new Transaction();
            $message_status_ok = 'Transfert effectué avec succès.';
            $message_status_nok = 'Un problème est survenu lors de la création du transfert';
        }

        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();
        // Data to return/display
        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $message_status_nok
        ];

        $form = $this->createForm(BankTransferType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $request->request->get('bank_transfer');

                list($transactionFrom) = $this->transactionManager->handleBankTransfer(
                    $this->user,
                    $default_bank_account->getId(),
                    (int) $data['bank_account_to'],
                    (int) $data['category'],
                    (float) $data['amount'],
                    new \DateTime($data['date']),
                    $data['label'],
                    !empty($data['details']) ? $data['details'] : null,
                    $is_edit ? $id_trans : null,
                );

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => $message_status_ok,
                    'entity' => self::format_json($transactionFrom),
                    'default_bank_account' => self::format_json_bank_account($default_bank_account)
                ];

                // Force old entity values into entity data (useful for JS edit)
                if ($is_edit && isset($old_entity_json))
                    $return_data['entity']['old'] = $old_entity_json;
            } catch (\Exception $e) {
                $this->entityManager->clear();
                $return_data['exception'] = $e->getMessage();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            // Redirect to dashboard
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * @Route("/transactions/{page}", name="transactions", defaults={"page"=1})
     */
    public function index(int $page): Response
    {
        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();

        // Force user to add or import transaction(s) first
        if (count($default_bank_account->getTransactions()) < 1)
            return $this->redirectToRoute('ignition-first-transaction');

        // Get nb pages of newsletter subscribes
        $nb_transactions = $this->transactionRepository->countAllByBankAccountAndByDate($default_bank_account);
        $nb_pages_raw = ($nb_transactions / self::NB_TRANSAC_BY_PAGE);
        $nb_pages = floor($nb_pages_raw);

        // If there is decimal numbers,
        //  there is less than 50 subs (=self::NB_TRANSAC_BY_PAGE) to display
        //  > So we need to add 1 more page
        if (($nb_pages_raw - $nb_pages) > 0)
            $nb_pages++;

        // Check if $page is correct, if not redirect with a correct page number
        if ($nb_pages > 0 && $page > $nb_pages) {
            $page = max(1, $page - 1);

            // Redirect with correct $page and filters in URI
            return $this->redirectToRoute('dashboard', [ 'page' => $page ]);
        }

        // Get incomes & expenses totals
        $total_incomes = $this->transactionRepository->findTotal($default_bank_account, null, null, 'incomes');
        $total_expenses = $this->transactionRepository->findTotal($default_bank_account, null, null, 'expenses');

        // Get transactions according to current page
        $transactions = $this->transactionRepository->findByBankAccountAndDateAndPage($default_bank_account, null, null, $page, self::NB_TRANSAC_BY_PAGE);

        // Get date start and date end
        $date_end = (isset($transactions[0]) && $page > 1) ? $transactions[0]->getDate()->format('Y-m-d') : null;
        $date_start = (count($transactions) > 1) ? $transactions[count($transactions)-1]->getDate()->format('Y-m-d') : null;

        return $this->render('transactions/index.html.twig', [
            'page_title'        => '<span class="icon icon-list"></span> ' . $this->translator->trans('page.transactions.title'),
            'meta'              => [ 'title' => $this->translator->trans('page.transactions.title') ],
            'core_class'        => 'app-core--transactions app-core--merge-body-in-header',
            // 'stylesheets'       => [ 'kb-dashboard.css' ],
            // 'scripts'           => [ 'kb-dashboard.js' ],
            'transactions'      => $transactions,
            'date_start'        => $date_start,
            'date_end'          => $date_end,
            'limit_max'         => self::NB_TRANSAC_BY_PAGE,
            'nb_transactions'   => $nb_transactions,
            'current_page'      => $page,
            'nb_pages'          => $nb_pages,
            'nb_by_page'        => self::NB_TRANSAC_BY_PAGE,
            'total_incomes'     => $total_incomes,
            'total_expenses'    => $total_expenses,
            'categories'       => $this->categoryRepository->findAllByUserId($this->user->getId()),
            'default_category' => $this->categoryRepository->findDefault(),
            'form_transaction'   => $this->createForm(TransactionType::class)->createView(),
            'form_category'      => $this->createForm(CategoryType::class)->createView(),
            'form_bank_transfer' => $this->user->hasManyBankAccounts() ? $this->createForm(BankTransferType::class)->createView() : null,
        ]);
    }

    /**
     * @Route("/transactions/import-csv", name="transaction_import_csv")
     * TODO: only tested with "Caisse d'épargne" CSV files,
     *          need to do more test with other banks files
     *          + custom import ? (TODO auto-detect fields + user validation)
     */
    public function importCSV(Request $request): Response
    {
        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        $csv_path = $request->files->get('first-import-file')->getPathName();

        // defaults
        $bank_account_total = 0;
        $file_total         = 0;
        $row                = 1;
        $bank_code          = false;

        if (($handle = fopen($csv_path, "r")) !== false) {
            // User has a bank account
            $default_bank_account = $this->user->getDefaultBankAccount();

            // Retrieve all categories
            $categories = $this->categoryRepository->findAll();
            // Default category
            foreach($categories as $cat) {
                // Retrieve default category
                if ($cat->getSlug() == 'misc')
                    $default_category = $cat;
            }

            // Loop on every CSV lines
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                $nb_fields = count($data);

                // Retrieve bank code
                if ($row == 1) {
                    $bank_code = explode(' : ', $data[0]);
                    $bank_code = (isset($bank_code[1])) ? (int) $bank_code[1] : false;
                }

                // Only for "Caisse d'épargne" bank (code = 18315)
                if ($bank_code !== false && $bank_code == 18315) {
                    if ($row == 4) {
                        $bank_account_total = (float) str_replace(',', '.', $data[4]);
                    } elseif($row > 5) {
                        // Is credit or debit valid ? (= col 3|4) AND with a description (= col 2)
                        if (($data[3] || $data[4]) && !empty($data[2])) {
                            $debit    = (float) str_replace(',', '.', $data[3]);
                            $credit   = (float) str_replace(',', '.', $data[4]);
                            $amount   = ($credit > 0) ? $credit : (($debit < 0) ? $debit : false);
                            $label    = trim($data[2]);
                            $details  = trim($data[5]);
                            $category = $this->findCategoryAccordingToLabel($categories, $label);

                            // Check amount before creating the new transaction
                            if ($amount !== false) {
                                $date     = explode('/', $data[0]);
                                $datetime = new \DateTime('20'.$date[2].'-'.$date[1].'-'.$date[0]);
                                // $datetime->setTimestamp(strtotime('20'.$date[2].'-'.$date[1].'-'.$date[0]));

                                // Create new transaction
                                $transaction = new Transaction();

                                // Add some data to transaction
                                $transaction
                                  ->setBankAccount($default_bank_account)
                                  ->setDate($datetime)
                                  ->setLabel($label)
                                  ->setAmount($amount)
                                  ->setCategory(!is_null($category) ? $category : $default_category);

                                if (!empty($details) && $details != $label)
                                    $transaction->setDetails($details);

                                // Save by persisting entity
                                $this->entityManager->persist($transaction);

                                // Increment total
                                $file_total += $amount;
                            } else {
                                // Invalid line
                                // TODO errors ?
                            }
                        }
                    }
                } else {
                    // Avort import
                    // TODO add error when bank code isn't found ! (file invalid ?)
                    break;
                }

                // increment row number
                $row++;
            }
            fclose($handle);
        }

        // Add a transaction to adjust the total to the bank account total
        if ($file_total != $bank_account_total) {
            $trans_adjustment = new Transaction();
            $amount_adjust    = $bank_account_total - $file_total;
            // Set adjustment data
            $trans_adjustment
              ->setBankAccount($default_bank_account)
              ->setDate(new \DateTime())
              ->setLabel('Ajustement suite à importation du fichier .csv')
              ->setAmount($amount_adjust)
              ->setCategory($default_category);

            // Persist adjustment
            $this->entityManager->persist($trans_adjustment);
        }

        /** @var Session $session */
        $session = $request->getSession();
        // 5) Try or clear the transactions
        try {
            // Flush OK !
            $this->entityManager->flush();
            $session->getFlashBag()->add('success', 'Importation des transactions effectuée avec succès.');
        } catch (\Exception $e) {
            $this->entityManager->clear();
            $session->getFlashBag()->add('error', $this->translator->trans('form.errors.generic'));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'bank_account_total' => $bank_account_total
            ]);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }

    private function findCategoryAccordingToLabel(array $categories, string $label): ?Category
    {
        $category = null;
        /** @var Category $cat */
        foreach($categories as $cat) {
            $regex = $cat->getImportRegex();
            // Retrieve category according to her regex on "label"
            if (!empty($regex)) {
                if (preg_match('/'.$regex.'/i', $label)) {
                    $category = $cat;
                    break;
                }
            }
        }

        return $category;
    }

    private static function format_json(Transaction $transaction): array
    {
        $category = $transaction->getCategory();

        $btltRaw = null;
        if ($transaction->getBankTransferLinkedTransaction()) {
            $btlt = $transaction->getBankTransferLinkedTransaction();
            $btltRaw = [
                'id' => $btlt->getId(),
                'amount' => $btlt->getAmount(),
                'bank_account_to' => [
                    'id' => $btlt->getBankAccount()->getId(),
                    'label' => $btlt->getBankAccount()->getLabel(),
                ],
            ];
        }

        return [
            'id'        => $transaction->getId(),
            'date'      => $transaction->getDate()->format('Y-m-d'),
            'amount'    => $transaction->getAmount(),
            'label'     => $transaction->getLabel(),
            'details'   => $transaction->getDetails(),
            'category'  => $category->getId(),
            'category_entity' => [
                'id'    => $category->getId(),
                'label' => $category->getLabel(),
                'icon'  => $category->getIcon(),
                'color' => $category->getColor(),
            ],
            'bank_transfer_linked_transaction' => $btltRaw,
        ];
    }

    private static function format_json_bank_account($bank_account): array
    {
        $currency = $bank_account->getCurrency();
        return [
            'id'        => $bank_account->getId(),
            'balance'   => round($bank_account->getBalance(), 2),
            'currency_entity' => [
                'id'    => $currency->getId(),
                'name'  => $currency->getName(),
                'label' => $currency->getLabel(),
                'slug'  => $currency->getSlug()
            ]
        ];
    }
}
