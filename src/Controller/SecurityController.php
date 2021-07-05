<?php

namespace App\Controller;

// Forms
use App\Form\UserType;

// Entities
use App\Entity\User;

// Components
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
// Mailer components
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;


class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription", name="user_registration")
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator)
    {
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // 3) Encode the password
            $password = $passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            // 4) Set default role
            $user->setRole('ROLE_USER');

            // 5) Save the User!
            $entityManager = $this->getDoctrine()->getManager();
            $repoUser = $entityManager->getRepository('App:User');
            $entityManager->persist($user);

            // 6) User already exist ?
            if(!empty($repoUser->loadUserByUsername($user->getUsername()))) {
                $request->getSession()->getFlashBag()->add('error', 'Cet utilisateur existe déjà, veuillez utiliser une adresse email différente.');
            } else {
                // 7) Try or clear
                try {
                    $entityManager->flush();
                    // Flush OK ! > Send email to user and redirect to dashboard

                    // Send email to user
                    // $message = (new Email())
                    //     ->from(new Address('ne-pas-repondre@kakeibo.fr', 'Kakeibo'))
                    //     ->to($user->getEmail())
                    //     ->subject('Confirmation d\'inscription')
                    //     ->html($this->renderView(
                    //         'emails/registration-confirm.html.twig',
                    //         [
                    //             'user' => $user,
                    //             // 'visitor' => [
                    //             //       'ip'    => $request->getClientIp(),
                    //             //       'agent' => $request->server->get('HTTP_USER_AGENT')
                    //             // ],
                    //         ]
                    //     ))
                    // ;
                    //
                    // $mailer->send($message);

                    // Add success message
                    $request->getSession()->getFlashBag()->add('success', 'Inscription effectuée avec succès, vous pouvez dès à présent vous connecter.');

                    // Redirect to login page
                    return $this->redirectToRoute('login');
                } catch (\Exception $e) {
                    // Something goes wrong
                    $request->getSession()->getFlashBag()->add('error', 'Une erreur inconnue est survenue, veuillez essayer de nouveau.');
                    $entityManager->clear();
                }
            }
        }

        return $this->render(
            'security/register.html.twig',
            array(
              'meta' => [ 'title' => $translator->trans('page.register.title') ],
              'form' => $form->createView()
            )
        );
    }


    /**
     * @Route("/connexion", name="login")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator)
    {
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
            'meta'          => [ 'title' => $translator->trans('page.login.title') ],
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
