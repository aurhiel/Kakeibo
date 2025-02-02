<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
  * @IsGranted("ROLE_ADMIN", message="You are not allowed to access the admin dashboard.")
  */
class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/{_locale}/admin", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Kakeibo / Admin')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Entities');
        yield MenuItem::linkToCrud('Categories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);

        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Back to Homepage', 'fa fa-arrow-left', '/dashboard');
    }
}
