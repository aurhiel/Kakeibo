<?php

namespace App\Controller;

use App\Entity\TransactionAuto;
use App\Entity\User;
use App\Form\TransactionAutoType;
use App\Repository\TransactionAutoRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class AutomatonController extends AbstractController
{
    private User $user;
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private TransactionAutoRepository $transactionAutoRepository;
    
    public function __construct(
        Security $security,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        TransactionAutoRepository $transactionAutoRepository
    ) {
        $this->user = $security->getUser();
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->transactionAutoRepository = $transactionAutoRepository;
    }

    /**
     * @Route("/automaton/{id}", name="automaton", defaults={"id"=null})
     */
    public function index(
        $id,
        Request $request
    ) {
        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        $is_edit = (!empty($id) && ((int) $id) > 0); // Edit transaction auto ?
        $return_data = [];

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();

        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();

        // Initialize default message & change entity if needed
        if($is_edit) {
            // Get transaction auto to edit with id AND user (for security)
            $trans_auto_entity = $this->transactionAutoRepository->findOneByIdAndUser($id, $this->user);
            $message_status_ok = $this->translator->trans('form_trans_auto.status.edit_ok');
            $message_status_nok = $this->translator->trans('form_trans_auto.status.edit_nok');
        } else {
            // New Entity
            $trans_auto_entity = new TransactionAuto();
            $message_status_ok = $this->translator->trans('form_trans_auto.status.add_ok');
            $message_status_nok = $this->translator->trans('form_trans_auto.status.add_nok');
        }

        // 1) Build the form
        $trans_auto_form = $this->createForm(TransactionAutoType::class, $trans_auto_entity, array('type_form' => ($is_edit ? 'edit' : 'add')));

        // 2) Handle the submit (will only happen on POST)
        $trans_auto_form->handleRequest($request);

        // 2.1) Form is submitted
        if ($trans_auto_form->isSubmitted()) {
            // Data to return/display
            $return_data = [
                'query_status' => 0,
                'slug_status' => 'error',
                'message_status' => $message_status_nok
            ];

            if ($trans_auto_form->isValid()) {
                // 3) Add some data to entity
                $trans_auto_entity->setBankAccount($default_bank_account);
                $trans_auto_entity->setIsActive(true);

                // 4) Save by persisting entity
                $this->entityManager->persist($trans_auto_entity);

                // 5) Try or clear
                try {
                    // Flush OK !
                    $this->entityManager->flush();

                    $return_data = [
                        'query_status' => 1,
                        'slug_status' => 'success',
                        'message_status' => $message_status_ok,
                        // Data
                        // 'entity' => self::format_json($trans_auto_entity),
                        // 'default_bank_account' => self::format_json_bank_account($default_bank_account)
                    ];
                } catch (\Exception $e) {
                    // Something goes wrong > Store exception message
                    $this->entityManager->clear();
                    $return_data['exception'] = $e->getMessage();
                }
            }
        }

        // Retrieve transactions auto
        $return_data['trans_auto'] = $this->transactionAutoRepository->findAllByBankAccount($default_bank_account);

        // Retrieve total auto expenses & incomes
        $return_data['total_auto_expenses'] = $this->transactionAutoRepository->findTotal($default_bank_account, 'expenses');
        $return_data['total_auto_incomes'] = $this->transactionAutoRepository->findTotal($default_bank_account, 'incomes');

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            // Set message in flashbag on direct access
            if (isset($return_data['slug_status']) && isset($return_data['message_status'])) {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);
            }

            // Redirect to home after form submit to clear it
            if ($trans_auto_form->isSubmitted())
                return $this->redirectToRoute('automaton');

            return $this->render('automaton/index.html.twig', [
                'core_class'  => 'app-core--automaton app-core--merge-body-in-header',
                'meta'        => [ 'title' => $this->translator->trans('page.trans_auto.meta.title') ],
                'page_title'  => '<span class="icon icon-command"></span> ' . $this->translator->trans('page.trans_auto.title'),
                'is_trans_auto_edit'    => $is_edit,
                'form_trans_auto'       => $trans_auto_form->createView(),
                'trans_auto'            => $return_data['trans_auto'],
                'total_auto_expenses'   => $return_data['total_auto_expenses'],
                'total_auto_incomes'    => $return_data['total_auto_incomes'],
                'ta_repeat_types_list'  => TransactionAuto::RT_LIST,
            ]);
        }
    }

    /**
     * @Route("/trans-auto/delete/{id}", name="automaton_trans_auto_delete")
     */
    public function trans_auto_delete(
        $id,
        Request $request
    ) {
        $trans_auto = $this->transactionAutoRepository->findOneByIdAndUser($id, $this->user);
        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $this->translator->trans('form_trans_auto.status.delete_nok')
        ];

        if(!is_null($trans_auto)) {
            $trans_auto_deleted = $trans_auto;
            $id_trans_auto_deleted = $trans_auto_deleted->getId();

            // Remove entity
            $this->entityManager->remove($trans_auto);

            // Try to save (flush) or clear entity remove
            try {
                // Flush OK !
                $this->entityManager->flush();
                // Retrieve user's default bank account
                // $default_bank_account = $this->user->getDefaultBankAccount();

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => $this->translator->trans('form_trans_auto.status.delete_ok'),
                    // Data
                    'entity' => [ 'id' => $id_trans_auto_deleted ],
                    // 'default_bank_account' => self::format_json_bank_account($default_bank_account)
                ];
            } catch (\Exception $e) {
                // Something goes wrong > Store exception message
                $this->entityManager->clear();
                $return_data['exception'] = $e->getMessage();
            }
        } else {
            $return_data['message_status'] = $this->translator->trans('form_trans_auto.status.delete_unknown_entity');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            // Set message in flashbag on direct access
            $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            // Redirect to previous page (= referer)
            return $this->redirect($request->headers->get('referer'));
        }
    }
}
