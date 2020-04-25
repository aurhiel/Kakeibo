<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class AutomatonController extends Controller
{
    /**
     * @Route("/automaton", name="automaton")
     */
    public function index()
    {
        if ($this->container->getParameter('kernel.environment') !== 'dev')
            return $this->redirectToRoute('dashboard');

        return $this->render('automaton/index.html.twig', [
            'page_title'      => '<span class="icon icon-command"></span> Récurrences',
            'controller_name' => 'AutomatonController',
        ]);
    }
}
