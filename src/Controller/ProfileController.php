<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;

/**
  * Require ROLE_USER for *every* controller method in this class.
  *
  * @IsGranted("ROLE_USER")
  */
class ProfileController extends AbstractController
{
    private User $user;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager
    ) {
        $this->user = $security->getUser();
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profil", name="user_profile")
     */
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var Session $session */
        $session = $request->getSession();

        $form = $this->createForm(UserType::class, $this->user, array('type_form' => 'edit'));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Change password only if is not default password (TODO find a better way)
            if($this->user->getPlainPassword() != '0ld-pa$$wo|2d') {
                $password = $passwordHasher->hashPassword($this->user, $this->user->getPlainPassword());
                $this->user->setPassword($password);
            }

            try {
                $session->getFlashBag()->add('success', 'Modification(s) effectuée(s) avec succès.');
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $session->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $this->entityManager->clear();
            }
        }

        return $this->render(
            'profile/index.html.twig',
            [
                'page_title'  => '<span class="icon icon-user"></span> Profil',
                'core_class'  => 'app-core--merge-body-in-header',
                'meta'        => array('title' => 'Profil'),
                'form'        => $form->createView()
            ]
        );
    }
}
