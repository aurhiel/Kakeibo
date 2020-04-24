<?php

namespace App\Controller;

// Forms
use App\Form\UserType;

// Entities
use App\Entity\User;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class ProfileController extends Controller
{
    /**
     * @Route("/{_locale}/profile", name="user_profile")
     */
    public function profile(AuthorizationCheckerInterface $authChecker, Request $request, Security $security, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $security->getUser();

        // 1) create the profile form
        $form = $this->createForm(UserType::class, $user, array('type_form' => 'edit'));

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 3) change password only if is not default password // TODO find a better way
            if($user->getPlainPassword() != '0ld-pa$$wo|2d') {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            }

            $entityManager = $this->getDoctrine()->getManager();

            // 4) Try or clear
            try {
                $request->getSession()->getFlashBag()->add('success', 'Modification(s) effectuée(s) avec succès.');
                $entityManager->flush();
                // Flush OK !
            } catch (\Exception $e) {
                // Something goes wrong
                $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                $entityManager->clear();
            }
        }

        return $this->render(
            'profile/index.html.twig',
            array(
                'page_title'  => '<span class="icon icon-user"></span> Profil',
                'core_class'  => 'app-core--merge-body-in-header',
                'meta'        => array('title' => 'Profil'),
                'form'        => $form->createView()
            )
        );
    }
}
