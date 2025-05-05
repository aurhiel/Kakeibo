<?php

namespace App\Controller;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Form\BankAccountType;
use App\Repository\BankAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BankAccountsController extends AbstractController
{
    private User $user;
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private BankAccountRepository $bankAccountRepository;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        BankAccountRepository $bankAccountRepository
    ) {
        $this->user = $security->getUser();
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->bankAccountRepository = $bankAccountRepository;
    }

    /**
     * @Route("/comptes-bancaires/{id}", name="bank_accounts", defaults={"id"=null})
     */
    public function index(?int $id, Request $request): Response
    {
        // Force user to create at least ONE bank account !
        if (count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        $is_edit = (!empty($id) && ((int) $id) > 0);
        $return_data = [];

        if ($is_edit) {
            $entity = $this->bankAccountRepository->findOneByIdAndUser($id, $this->user);
            $message_status_ok = $this->translator->trans('form_bank_account.status.edit_ok');
            $message_status_nok = $this->translator->trans('form_bank_account.status.edit_nok');
        } else {
            $entity = new BankAccount($this->user);
            $message_status_ok = $this->translator->trans('form_bank_account.status.add_ok');
            $message_status_nok = $this->translator->trans('form_bank_account.status.add_nok');
        }

        $form = $this->createForm(BankAccountType::class, $entity, ['type_form' => $is_edit ? 'edit' : 'add']);
        $form->handleRequest($request);

        // Handle the submitted form
        if ($form->isSubmitted()) {
            $return_data = [ 'slug_status' => 'error', 'message_status' => $message_status_nok ];

            if ($form->isValid()) {
                $this->entityManager->persist($entity);

                try {
                    $this->entityManager->flush();

                    $return_data = [ 'slug_status' => 'success', 'message_status' => $message_status_ok ];
                } catch (\Exception $e) {
                    $this->entityManager->clear();
                    $return_data['message_status'] = $this->translator->trans('form.errors.generic');
                    $return_data['exception'] = $e->getMessage();
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            if (isset($return_data['slug_status']) && isset($return_data['message_status'])) {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);
            }

            if ($form->isSubmitted())
                return $this->redirectToRoute('bank_accounts');

            return $this->render('bank-accounts/index.html.twig', [
                'core_class'  => 'app-core--bank-accounts app-core--merge-body-in-header',
                'meta' => [ 'title' => $this->translator->trans('page.bank_accounts.title') ],
                'page_title' => '<span class="icon icon-book"></span> ' . $this->translator->trans('page.bank_accounts.title'),
                'is_bank_account_edit' => $is_edit,
                'form_bank_account' => $form->createView(),
            ]);
        }
    }

    /**
     * @Route("/compte-bancaires/delete/{id}", name="bank_accounts_delete")
     */
    public function delete(int $id, Request $request): Response
    {
        $entity = $this->bankAccountRepository->findOneByIdAndUser($id, $this->user);
        $return_data = [
            'slug_status' => 'error',
            'message_status' => $this->translator->trans('form_bank_account.status.delete_nok')
        ];

        if(!is_null($entity) && false === $entity->isDefault()) {
            $entity_deleted = $entity;
            $id_entity_deleted = $entity_deleted->getId();

            $this->entityManager->remove($entity);

            try {
                $this->entityManager->flush();

                $return_data = [
                    'slug_status' => 'success',
                    'message_status' => $this->translator->trans('form_bank_account.status.delete_ok'),
                    // Data
                    'entity' => [ 'id' => $id_entity_deleted ],
                    // 'default_bank_account' => self::format_json_bank_account($this->user->getDefaultBankAccount())
                ];
            } catch (\Exception $e) {
                $this->entityManager->clear();
                $return_data['message_status'] = $this->translator->trans('form.errors.generic');
                $return_data['exception'] = $e->getMessage();
            }
        } else {
            $return_data['message_status'] = $this->translator->trans(sprintf(
                'form_bank_account.status.%s',
                $entity->isDefault() ? 'delete_default_nok' : 'delete_unknown_entity',
            ));
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
