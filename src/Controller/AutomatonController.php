<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class AutomatonController extends AbstractController
{
    /**
     * @Route("/automaton", name="automaton")
     */
    public function index()
    {
        if ($this->getParameter('kernel.environment') !== 'dev')
            return $this->redirectToRoute('dashboard');

        return $this->render('automaton/index.html.twig', [
            'page_title'      => '<span class="icon icon-command"></span> Récurrences',
            'controller_name' => 'AutomatonController',
        ]);
    }
}
