<?php

namespace App\Controller;

// Forms
use App\Form\TransactionAutoType;

// Entities
use App\Entity\TransactionAuto;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    /**
     * @Route("/automaton/{id}", name="automaton", defaults={"id"=null})
     */
    public function index($id, Request $request, Security $security, TranslatorInterface $translator)
    {
        // Retrieve user object
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // Get some data
        $em             = $this->getDoctrine()->getManager();
        $r_trans_auto   = $em->getRepository(TransactionAuto::class);
        $return_data    = [];

        // Edit transaction auto ?
        $is_edit = (!empty($id) && ((int) $id) > 0);

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // Initialize default message & change entity if needed
        if($is_edit) {
            // Get transaction auto to edit with id AND user (for security)
            $trans_auto_entity  = $r_trans_auto->findOneByIdAndUser($id, $user);
            $message_status_ok  = 'Modificiation de la transaction récurrente effectuée.';
            $message_status_nok = 'Un problème est survenu lors de la modification de la transaction récurrente';
        } else {
            // New Entity
            $trans_auto_entity  = new TransactionAuto();
            $message_status_ok  = 'Ajout de la transaction récurrente effectuée.';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la transaction récurrente';
        }

        // 1) Build the form
        $trans_auto_form = $this->createForm(TransactionAutoType::class, $trans_auto_entity, array('type_form' => ($is_edit ? 'edit' : 'add')));

        // 2) Handle the submit (will only happen on POST)
        $trans_auto_form->handleRequest($request);

        // 2.1) Form is submitted
        if ($trans_auto_form->isSubmitted()) {
            // Data to return/display
            $return_data = [
                'query_status'      => 0,
                'slug_status'       => 'error',
                'message_status'    => $message_status_nok
            ];

            if ($trans_auto_form->isValid()) {
                // 3) Add some data to entity
                $trans_auto_entity->setBankAccount($default_bank_account);
                $trans_auto_entity->setIsActive(true);

                // 4) Save by persisting entity
                $em->persist($trans_auto_entity);

                // 5) Try or clear
                try {
                    // Flush OK !
                    $em->flush();

                    $return_data = [
                        'query_status'    => 1,
                        'slug_status'     => 'success',
                        'message_status'  => $message_status_ok,
                        // Data
                        // 'entity'                => self::format_json($trans_auto_entity),
                        // 'default_bank_account'  => self::format_json_bank_account($default_bank_account)
                    ];
                } catch (\Exception $e) {
                    // Something goes wrong
                    $em->clear();
                    // Store exception message
                    $return_data['exception'] = $e->getMessage();
                }
            }
        }

        // Retrieve transactions auto
        $return_data['trans_auto'] = $r_trans_auto->findAllByBankAccount($default_bank_account);

        // Retrieve total auto expenses & incomes
        $return_data['total_auto_expenses'] = $r_trans_auto->findTotal($default_bank_account, 'expenses');
        $return_data['total_auto_incomes'] = $r_trans_auto->findTotal($default_bank_account, 'incomes');

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            // Set message in flashbag on direct access
            if (isset($return_data['slug_status']) && isset($return_data['message_status']))
                $request->getSession()->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            if (isset($return_data['exception']))
                dump($return_data['exception']);

            // Redirect to home after form submit to clear it
            if ($trans_auto_form->isSubmitted())
              return $this->redirectToRoute('automaton');

            return $this->render('automaton/index.html.twig', [
                'core_class'  => 'app-core--automaton app-core--merge-body-in-header',
                'meta'        => [ 'title' => $translator->trans('page.trans_auto.meta.title') ],
                'page_title'  => '<span class="icon icon-command"></span> ' . $translator->trans('page.trans_auto.title'),
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
     * @Route("/automaton/trans-auto/delete/{id}", name="automaton_trans_auto_delete")
     */
    public function trans_auto_delete($id, Request $request, Security $security, TranslatorInterface $translator)
    {
        dump($id);
        exit;
    }
}
