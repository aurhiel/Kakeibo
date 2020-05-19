<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
      return $this->render(
          'home/index.html.twig',
          array(
              'core_class'  => 'app-core--home',
              'stylesheets' => [ 'kb-home.css' ],
              'scripts'     => [ 'kb-home.js' ],
              'meta'        => array('title' => '')
          )
      );
    }
}
