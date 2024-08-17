<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render(
            'home/index.html.twig',
            [
                'core_class'  => 'app-core--home',
                'stylesheets' => [ 'kb-home' ],
                'scripts'     => [ 'kb-home' ],
                'meta'        => ['title' => '']
            ]
        );
    }
}
