<?php

namespace App\Controller;

// Forms
use App\Form\TransactionType;

// Entities
use App\Entity\Transaction;
use App\Entity\Category;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;

class TransactionsController extends Controller
{
    /**
     * @Route("/transactions", name="transactions")
     */
    public function index(Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // Build the transaction form
        $trans_entity = new Transaction();
        $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

        // Force user to add or import transaction(s) first
        if (count($default_bank_account->getTransactions()) < 1)
            return $this->redirectToRoute('ignition-first-transaction');

        // $em = $this->getDoctrine()->getManager();
        // $r_trans = $em->getRepository(Transaction::class);
        // dump($r_trans->findByBankAccount($default_bank_account));

        return $this->render('transactions/index.html.twig', [
            'page_title'            => '<span class="icon icon-edit"></span> Transactions',
            'core_class'            => 'app-core--transactions app-core--merge-body-in-header',
            // 'stylesheets'           => [ 'kb-dashboard.css' ],
            // 'scripts'               => [ 'kb-dashboard.js' ],
            'user'                  => $user,
            'current_bank_account'  => $default_bank_account,
            'transactions'          => $default_bank_account->getTransactions(),
            'form_transaction'      => $trans_form->createView()
        ]);
    }

    /**
     * @Route("/trans/manage", name="transaction_manage")
     */
    public function manage(Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        // 1) Build the form
        $trans_entity = new Transaction();
        $trans_form   = $this->createForm(TransactionType::class, $trans_entity);

        // 2) Handle the submit (will only happen on POST)
        $trans_form->handleRequest($request);
        if ($trans_form->isSubmitted() && $trans_form->isValid()) {
            $em   = $this->getDoctrine()->getManager();
            $now  = new \DateTime();

            // Force transaction time to the moment when user add it
            $trans_date = $trans_entity->getDate();
            $trans_date->setTime($now->format('H'), $now->format('i'), $now->format('s'));
            $trans_entity->setDate($trans_date);

            // 3) Add some data to entity
            $trans_entity->setBankAccount($default_bank_account);

            // 4) Save by persisting entity
            $em->persist($trans_entity);

            // 5) Try or clear
            try {
                // Flush OK !
                $em->flush();

                // Add success message
                $request->getSession()->getFlashBag()->add('success', 'Ajout de la transaction effectuée avec succès.');

                // Redirect to Dashboard
                return $this->redirectToRoute('dashboard');
            } catch (\Exception $e) {
                // Something goes wrong
                $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $em->clear();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($trans_entity);
        } else {
            // No direct access
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * @Route("/trans/remove", name="transaction_remove")
     */
    public function remove()
    {

    }


    /**
     * @Route("/trans/import-csv", name="transaction_import_csv")
     * TODO: only tested with "Caisse d'épargne" CSV files,
     *          need to do more test with other banks files
     */
    public function import_csv(Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        $csv_path = $request->files->get('first-import-file')->getPathName();

        // defaults
        $bank_account_total = 0;
        $file_total         = 0;
        $row                = 1;
        $bank_code          = false;

        if (($handle = fopen($csv_path, "r")) !== false) {
            $em = $this->getDoctrine()->getManager();

            // User has a bank account
            $default_bank_account = $user->getDefaultBankAccount();

            // Retrieve all categories
            $r_category = $em->getRepository(Category::class);
            $categories = $r_category->findAll();
            // Default category
            foreach($categories as $cat) {
                $regex = $cat->getImportRegex();
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
                            $category = self::findCategoryAccordingToLabel($categories, $label);

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
                                $em->persist($transaction);

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
              ->setLabel("Ajustement suite à importation du fichier .csv")
              ->setAmount($amount_adjust)
              ->setCategory($default_category);

            // Persist adjustment
            $em->persist($trans_adjustment);
        }

        // 5) Try or clear the transactions
        try {
            // Flush OK !
            $em->flush();
            // Add success message
            $request->getSession()->getFlashBag()->add('success', 'Importation des transactions effectuée avec succès.');
        } catch (\Exception $e) {
            dump($e);
            // Something goes wrong
            $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
            $em->clear();
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

    public static function findCategoryAccordingToLabel($categories, $label)
    {
        $category = null;
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
}
