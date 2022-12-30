<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\Slugger;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class CategoriesController extends AbstractController
{
    private User $user;
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository
    ) {
        $this->user = $security->getUser();
        $this->entityManager = $entityManager;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @Route("/categories/manage", name="category_manage")
     */
    public function index(
        Request $request,
        Slugger $slugger
    ): Response {
        $id_cat = (int) $request->request->get('id');
        $is_edit = (!empty($id_cat) && $id_cat > 0);

        if($is_edit) {
            $cat_entity   = $this->categoryRepository->findOneByIdAndUser($id_cat, $this->user);
            $old_cat_json = self::format_json($cat_entity);
            $message_status_ok  = 'Modificiation de la catégorie effectuée.';
            $message_status_nok = 'Un problème est survenu lors de la modification de la catégorie';
        } else {
            $cat_entity = new Category();
            $message_status_ok  = 'Ajout de la catégorie effectuée.';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la catégorie';
        }

        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $message_status_nok,
        ];

        $cat_form = $this->createForm(CategoryType::class, $cat_entity);
        $cat_form->handleRequest($request);

        if ($cat_form->isSubmitted() && $cat_form->isValid()) {
            $cat_entity->setUser($this->user);
            $cat_entity->setSlug($slugger->slugify($cat_entity->getLabel()));
            $cat_entity->setIsDefault(false);

            $this->entityManager->persist($cat_entity);

            try {
                // Flush OK !
                $this->entityManager->flush();

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => $message_status_ok,
                    'entity' => self::format_json($cat_entity),
                ];

                // Force old entity values into entity data (useful for JS edit)
                if ($is_edit && isset($old_cat_json))
                    $return_data['entity']['old'] = $old_cat_json;
            } catch (\Exception $e) {
                // Something goes wrong > Store exception message
                $this->entityManager->clear();
                $return_data['exception'] = $e->getMessage();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            return $this->redirectToRoute('dashboard');
        }
    }

    private static function format_json(Category $category)
    {
        return [
            'id'    => $category->getId(),
            'label' => $category->getLabel(),
            'icon'  => $category->getIcon(),
            'color' => $category->getColor()
        ];
    }
}
