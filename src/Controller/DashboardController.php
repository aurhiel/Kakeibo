<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Security;

class DashboardController extends Controller
{
    /**
     * @Route("/", name="dashboard")
     */
    public function index(Security $security, Request $request)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if(count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();

        return $this->render('dashboard/index.html.twig', [
            'page_title'            => 'Dashboard',
            'user'                  => $user,
            'current_bank_account'  => $default_bank_account,
            'has_transactions'      => (count($default_bank_account->getTransactions()) > 0)
        ]);
    }
}
