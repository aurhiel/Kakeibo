<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
  * Require ROLE_ADMIN for *every* controller method in this class.
  *
  * @IsGranted("ROLE_ADMIN")
  */
class AdminController extends AbstractController
{
    private TranslatorInterface $translator;
    private UserRepository $userRepository;
    private CategoryRepository $categoryRepository;
    private TransactionRepository $transactionRepository;

    public function __construct(
        TranslatorInterface $translator,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'page_title' => '<span class="icon icon-settings"></span> ' . $this->translator->trans('page.admin.title'),
            'core_class' => 'app-core--admin app-core--merge-body-in-header',
            'users' => $this->userRepository->findAllBy(['registerDate' => 'DESC'], 5),
            'nb_users' => $this->userRepository->countAll(),
            'max_users' => $this->getParameter('app.max_users'),
            'nb_categories' => $this->categoryRepository->countAll(),
            'nb_transactions' => $this->transactionRepository->countAll(),
        ]);
    }

    /**
     * @Route("/admin/utilisateurs", name="admin_users")
     */
    public function users(): Response
    {
        // TODO add pagination & re-order

        return $this->render('admin/users.html.twig', [
            'page_title' => '<span class="icon icon-user"></span> ' . $this->translator->trans('page.admin.title'),
            'core_class' => 'app-core--admin app-core--merge-body-in-header',
            'users' => $this->userRepository->findAllBy(['registerDate' => 'DESC']),
        ]);
    }

    /**
     * @Route("/admin/categories", name="admin_categories")
     */
    public function categories(): Response
    {
        // TODO Get categories & create form to add new ones !

        return $this->render('admin/categories.html.twig', [
            'page_title' => '<span class="icon icon-pie-chart"></span> ' . $this->translator->trans('page.admin.title'),
            'core_class' => 'app-core--admin app-core--merge-body-in-header',
            'categories' => $this->categoryRepository->findAll(),
        ]);
    }
}
