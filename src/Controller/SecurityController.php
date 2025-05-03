<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;


class SecurityController extends AbstractController
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/inscription", name="user_registration")
     */
    public function register(
      Request $request,
      AuthorizationCheckerInterface $authChecker,
      UserPasswordHasherInterface $passwordHasher,
      MailerInterface $mailer,
      UserRepository $userRepository
    ): Response {
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        /** @var Session $session */
        $session = $request->getSession();
        $max_users = $this->getParameter('app.max_users');
        $max_reached = ($max_users != null && $userRepository->countAll() >= $max_users);

        // 1) Build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 1.1) Check if spaces left to register a new user
        if (false === $max_reached) {
            // 2) Handle the submit (will only happen on POST)
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // 3) Encode the password
                $password = $passwordHasher->hashPassword($user, $user->getPlainPassword());
                $user->setPassword($password);

                // 4) Set default role
                $user->setRole('ROLE_USER');

                // 5) Save the User!
                $this->entityManager->persist($user);

                // 6) User already exist ?
                if(!empty($userRepository->loadUserByUsername($user->getUsername()))) {
                  $session->getFlashBag()->add('error', $this->translator->trans('page.register.messages.already_registered'));
                } else {
                    // 7) Try or clear
                    try {
                        $this->entityManager->flush();
                        // Flush OK ! > Send email to user and redirect to dashboard

                        // TODO Send email to user in order to validate his subscribe
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
                        $session->getFlashBag()->add('success', $this->translator->trans('page.register.messages.registration_success'));

                        // Redirect to login page
                        return $this->redirectToRoute('login');
                    } catch (\Exception $e) {
                        $session->getFlashBag()->add('error', $this->translator->trans('form.errors.generic'));
                        $this->entityManager->clear();
                    }
                }
            }
        } else {
            // Set message that max users amount is reached
            $session->getFlashBag()->add('notice', $this->translator->trans('page.register.messages.max_users_reached'));
        }

        return $this->render(
            'security/register.html.twig',
            [
                'meta' => [ 'title' => $this->translator->trans('page.register.title') ],
                'form' => $form->createView(),
                'max_users_reached' => $max_reached,
            ]
        );
    }


    /**
     * @Route("/connexion", name="login")
     */
    public function login(
        AuthenticationUtils $authenticationUtils,
        AuthorizationCheckerInterface $authChecker
    ): Response {
        if (true === $authChecker->isGranted('IS_AUTHENTICATED_FULLY'))
            return $this->redirectToRoute('dashboard');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
            'meta'          => [ 'title' => $this->translator->trans('page.login.title') ],
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
