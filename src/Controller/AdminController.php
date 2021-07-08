<?php

namespace App\Controller;

// Entities
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_ADMIN for *every* controller method in this class.
  *
  * @IsGranted("ROLE_ADMIN")
  */
class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index(TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        // Retrieve users
        $r_user = $em->getRepository(User::class);
        $users  = $r_user->findBy([], ['id' => 'DESC']);

        dump($users);

        return $this->render('admin/index.html.twig', [
            'page_title'  => '<span class="icon icon-settings"></span> ' . $translator->trans('page.admin.title'),
            'core_class'  => 'app-core--admin app-core--merge-body-in-header',
            'users'       => $users,
        ]);
    }
}
