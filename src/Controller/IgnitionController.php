<?php

namespace App\Controller;

// Forms
use App\Form\BankAccountType;

// Entities
use App\Entity\BankAccount;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IgnitionController extends Controller
{

    private $nb_steps = 2;

    /**
    * @Route("/demarrage", name="ignition-home")
    */
    public function index()
    {

    }


    /**
     * @Route("/demarrage/creation-premier-compte", name="ignition-first-bank-account")
     */
    public function first_bank_account(Security $security, Request $request)
    {
        $user = $security->getUser();

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

                // Redirect to dashboard
                // TODO add step to add first or import first transaction(s), see first_transaction() above
                return $this->redirectToRoute('dashboard');
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
            'form_bank_account' => $ba_form->createView(),
        ]);
    }


    /**
     * @Route("/demarrage/ajout-premiere-transaction", name="ignition-first-transaction")
     */
    public function first_transaction(Security $security, Request $request)
    {
        $user = $security->getUser();

        dump($user);
    }
}
