<?php

namespace App\Controller;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistics", name="statistics")
     */
    public function index(Security $security)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if(count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        // User has a bank account
        $default_bank_account = $user->getDefaultBankAccount();
        if(count($default_bank_account->getTransactions()) < 1) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('statistics/index.html.twig', [
            'page_title'      => '<span class="icon icon-bar-chart"></span> Statistiques',
            'controller_name' => 'StatisticsController',
        ]);
    }
}
