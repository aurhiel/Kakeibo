<?php

namespace App\Controller;

// Forms
use App\Form\CategoryType;

// Entities
use App\Entity\Category;

// Services
use App\Service\Slugger;

use Symfony\Component\HttpFoundation\Response;
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
    /**
     * @Route("/categories/manage", name="category_manage")
     */
    public function index(Security $security, Request $request, Slugger $slugger): Response
    {
        /**
         * @var User $user
         */
        $user = $security->getUser();
        $em = $this->getDoctrine()->getManager();

        /**
         * @var CategoryRepository $r_cat
         */
        $r_cat = $em->getRepository(Category::class);
        $id_cat = (int) $request->request->get('id');
        $is_edit = (!empty($id_cat) && $id_cat > 0);

        if($is_edit) {
            // Get category to edit with id AND user (for security)
            $cat_entity   = $r_cat->findOneByIdAndUser($id_cat, $user);
            $old_cat_json = self::format_json($cat_entity);
            $message_status_ok  = 'Modificiation de la catégorie effectuée.';
            $message_status_nok = 'Un problème est survenu lors de la modification de la catégorie';
        } else {
            // New Entity
            $cat_entity = new Category();
            $message_status_ok  = 'Ajout de la catégorie effectuée.';
            $message_status_nok = 'Un problème est survenu lors de l\'ajout de la catégorie';
        }

        // Data to return/display
        $return_data = [
            'query_status' => 0,
            'slug_status' => 'error',
            'message_status' => $message_status_nok,
        ];

        // 1) Build the form
        $cat_form = $this->createForm(CategoryType::class, $cat_entity);

        // 2) Handle the submit (will only happen on POST)
        $cat_form->handleRequest($request);

        if ($cat_form->isSubmitted() && $cat_form->isValid()) {
            // 3) Add some data to entity
            $cat_entity->setUser($user);
            $cat_entity->setSlug($slugger->slugify($cat_entity->getLabel()));
            $cat_entity->setIsDefault(false);

            // 4) Save by persisting entity
            $em->persist($cat_entity);

            // 5) Try or clear
            try {
                // Flush OK !
                $em->flush();

                $return_data = [
                    'query_status' => 1,
                    'slug_status' => 'success',
                    'message_status' => $message_status_ok,
                    // Data
                    'entity' => self::format_json($cat_entity),
                ];

                // Force old entity values into entity data (useful for JS edit)
                if ($is_edit && isset($old_cat_json))
                    $return_data['entity']['old'] = $old_cat_json;
            } catch (\Exception $e) {
                // Something goes wrong
                $em->clear();
                // Store exception message
                $return_data['exception'] = $e->getMessage();
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json($return_data);
        } else {
            // Set message in flashbag on direct access
            $request->getSession()->getFlashBag()->add($return_data['slug_status'], $return_data['message_status']);

            // Redirect to dashboard
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
