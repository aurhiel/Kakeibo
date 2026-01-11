<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

#[IsGranted('ROLE_USER')]
class DemoController extends AbstractController
{
    #[Route('/demo', name: 'demo')]
    public function index(Security $security): Response
    {
        /** @var User $user */
        $user = $security->getUser();

        // Force user to create at least ONE bank account !
        if (count($user->getBankAccounts()) < 1) {
            return $this->redirectToRoute('ignition-first-bank-account');
        }

        return $this->render('demo/index.html.twig', [
            'page_title' => '<span class="icon icon-save"></span> Demo',
        ]);
    }
}
