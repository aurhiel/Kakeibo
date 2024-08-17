<?php

namespace App\Controller;

use App\Entity\BankAccount;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\BankAccountType;
use App\Form\TransactionType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class IgnitionController extends AbstractController
{
    private const NB_STEPS = 2;

    private User $user;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->user = $security->getUser();
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/demarrage/creation-premier-compte", name="ignition-first-bank-account")
     */
    public function first_bank_account(Request $request): Response
    {
        if (count($this->user->getBankAccounts()) > 0)
            return $this->redirectToRoute('ignition-first-transaction');

        /** @var Session $session */
        $session = $request->getSession();

        // 1) build the form
        $ba_entity  = new BankAccount($this->user);
        $ba_form    = $this->createForm(BankAccountType::class, $ba_entity);

        // 2) handle the submit (will only happen on POST)
        $ba_form->handleRequest($request);
        if ($ba_form->isSubmitted() && $ba_form->isValid()) {
            // 3) Save by persisting entity
            $this->entityManager->persist($ba_entity);

            // First bank account = default bank account
            $ba_entity->setIsDefault(true);

            // 4) Try or clear
            try {
                // Flush OK !
                $this->entityManager->flush();

                // Add success message
                $session->getFlashBag()->add('success', 'Création de votre 1er compte effectuée avec succès.');

                // Redirect to first transaction
                return $this->redirectToRoute('ignition-first-transaction');
            } catch (\Exception $e) {
                // Something goes wrong
                $session->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $this->entityManager->clear();
            }
        }

        return $this->render('ignition/first-bank-account.html.twig', [
            // Metas
            'meta' => [
                'title' => 'Création du 1er compte'
            ],
            'step' => 1,
            'nb_steps' => self::NB_STEPS,
            'form_bank_account' => $ba_form->createView(),
        ]);
    }

    /**
     * @Route("/demarrage/ajout-premiere-transaction", name="ignition-first-transaction")
     */
    public function first_transaction(Request $request): Response
    {
        // Force user to create at least ONE bank account !
        if (empty($this->user) || count($this->user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        /** @var Session $session */
        $session = $request->getSession();

        // User has a bank account
        $default_bank_account = $this->user->getDefaultBankAccount();

        // If user has some transactions > redirect to dashboard
        if (count($default_bank_account->getTransactions()) > 0)
            return $this->redirectToRoute('dashboard');

        // 1) Build the form
        $trans_entity = new Transaction();
        $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

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

                // Add success message
                $session->getFlashBag()->add('success', 'Ajout de votre 1ère transaction effectuée avec succès.');

                // Redirect to Dashboard
                return $this->redirectToRoute('dashboard');
            } catch (\Exception $e) {
                // Something goes wrong
                $session->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $this->entityManager->clear();
            }
        }

        return $this->render('ignition/first-transaction-or-import.html.twig', [
            // Metas
            'meta' => [
                'title' => 'Ajouter ou importer une 1ère transaction'
            ],
            'step' => 2,
            'nb_steps' => self::NB_STEPS,
            'hide_creation_center' => true,
            'form_transaction_submitted' => $trans_form->isSubmitted(),
            'form_transaction' => $trans_form->createView()
        ]);
    }
}
