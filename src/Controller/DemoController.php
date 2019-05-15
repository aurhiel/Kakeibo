<?php

namespace App\Controller;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DemoController extends Controller
{
    /**
     * @Route("/demo", name="demo")
     */
    public function index(Security $security)
    {
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if(count($user->getBankAccounts()) < 1)
            return $this->redirectToRoute('ignition-first-bank-account');

        return $this->render('demo/index.html.twig', [
            'controller_name' => 'DemoController',
        ]);
    }
}
