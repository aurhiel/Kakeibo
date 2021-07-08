<?php

namespace App\Controller;

// Entities
use App\Entity\User;
use App\Entity\Category;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        $users  = $r_user->findBy([], ['registerDate' => 'DESC']);
        $nb_users = count($users);

        // Retrieve nb categories
        $r_category = $em->getRepository(Category::class);

        return $this->render('admin/index.html.twig', [
            'page_title'  => '<span class="icon icon-settings"></span> ' . $translator->trans('page.admin.title'),
            'core_class'  => 'app-core--admin app-core--merge-body-in-header',
            'users'       => array_slice($users, 0, min(5, $nb_users)),
            'nb_users'    => $nb_users,
            'max_users'   => $this->getParameter('app.max_users'),
            'nb_categories' => (int)$r_category->countAll()
        ]);
    }

    /**
     * @Route("/admin/utilisateurs", name="admin_users")
     */
    public function users(TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        // Retrieve users
        $r_user = $em->getRepository(User::class);
        $users  = $r_user->findBy([], ['registerDate' => 'DESC']);

        // TODO add pagination & re-order

        return $this->render('admin/users.html.twig', [
            'page_title'  => '<span class="icon icon-user"></span> ' . $translator->trans('page.admin.title'),
            'core_class'  => 'app-core--admin app-core--merge-body-in-header',
            'users'       => $users,
        ]);
    }

    /**
     * @Route("/admin/categories", name="admin_categories")
     */
    public function categories(TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        // TODO Get categories & create form to add new ones !

        // Retrieve categories
        $r_category = $em->getRepository(Category::class);
        $categories = $r_category->findAll();

        return $this->render('admin/categories.html.twig', [
            'page_title' => '<span class="icon icon-pie-chart"></span> ' . $translator->trans('page.admin.title'),
            'core_class' => 'app-core--admin app-core--merge-body-in-header',
            'categories' => $categories,
        ]);
    }
}
