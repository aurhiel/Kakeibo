<?php

namespace App\Controller;

// Forms
use App\Form\BankAccountType;
use App\Form\TransactionType;
use App\Form\TransactionImportType;

// Entities
use App\Entity\BankAccount;
use App\Entity\Transaction;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IgnitionController extends Controller
{

    private $nb_steps = 2;

    /**
     * @Route("/demarrage/creation-premier-compte", name="ignition-first-bank-account")
     */
    public function first_bank_account(Security $security, Request $request)
    {
        $user = $security->getUser();

        if (count($user->getBankAccounts()) > 0)
            return $this->redirectToRoute('ignition-first-transaction');

        // 1) build the form
        $ba_entity  = new BankAccount($user);
        $ba_form    = $this->createForm(BankAccountType::class, $ba_entity);

        // 2) handle the submit (will only happen on POST)
        $ba_form->handleRequest($request);
        if ($ba_form->isSubmitted() && $ba_form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // 3) Save by persisting entity
            $em->persist($ba_entity);

            // First bank account = default bank account
            $ba_entity->setIsDefault(true);

            // 4) Try or clear
            try {
                // Flush OK !
                $em->flush();

                // Add success message
                $request->getSession()->getFlashBag()->add('success', 'Création de votre 1er compte effectuée avec succès.');

                // Redirect to first transaction
                return $this->redirectToRoute('ignition-first-transaction');
            } catch (\Exception $e) {
                // Something goes wrong
                $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $em->clear();
            }
        }

        return $this->render('ignition/first-bank-account.html.twig', [
            // Metas
            'meta' => [
                'title' => 'Création du 1er compte'
            ],
            'step'      => 1,
            'nb_steps'  => $this->nb_steps,
            'form_bank_account' => $ba_form->createView(),
        ]);
    }


    /**
     * @Route("/demarrage/ajout-premiere-transaction", name="ignition-first-transaction")
     */
    public function first_transaction(Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // If user has some transactions > redirect to dashboard
        if (count($default_bank_account->getTransactions()) > 0)
            return $this->redirectToRoute('dashboard');

        // 1) Build the form
        $trans_entity = new Transaction($user);
        $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

        // 2) Handle the submit (will only happen on POST)
        $trans_form->handleRequest($request);
        if ($trans_form->isSubmitted() && $trans_form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // 3) Add some data to entity
            $trans_entity->setBankAccount($default_bank_account);

            // 4) Save by persisting entity
            $em->persist($trans_entity);

            // 5) Try or clear
            try {
                // Flush OK !
                $em->flush();

                // Add success message
                $request->getSession()->getFlashBag()->add('success', 'Ajout de votre 1ère transaction effectuée avec succès.');

                // Redirect to Dashboard
                return $this->redirectToRoute('dashboard');
            } catch (\Exception $e) {
                dump($e);

                // Something goes wrong
                $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $em->clear();
            }
        }

        return $this->render('ignition/first-transaction-or-import.html.twig', [
            // Metas
            'meta' => [
                'title' => 'Ajouter ou importer une 1ère transaction'
            ],
            'step'      => 2,
            'nb_steps'  => $this->nb_steps,
            'current_bank_account'        => $default_bank_account,
            'form_transaction_submitted'  => $trans_form->isSubmitted(),
            'form_transaction'            => $trans_form->createView()
        ]);
    }
}
