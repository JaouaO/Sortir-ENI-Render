<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Helper\FileUploader;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Constraint\FileExists;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[Route('/utilisateur', name: 'user')]
final class UserController extends AbstractController
{
    #[Route('/index', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig');
    }

    #[Route(path: '/connexion', name: '_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: '_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: '_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag, FileUploader $fileUploader): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {
                $fileName = $fileUploader->upload(
                    $file,
                    $user->getPseudo(),
                    $parameterBag->get('photo')['photo_profile']
                );
                $user->setPoster($fileName);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('user_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('test@sortie.com', 'Sortie Mail Bot'))
                    ->to((string)$user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // do anything else you need here, like send an email

            return $this->redirectToRoute('user_profile', ['id' => $user->getId()]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'mode' => 'register'
        ]);
    }

    #[Route('/verify/email', name: '_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('user_profile', [
                'id' => $user->getId(),
            ]);
        }
        $this->addFlash('success', 'Votre adresse mail à été validé.');
        return $this->redirectToRoute('user_register');
    }

    #[Route('/profile/{id}', name: '_profile')]
    public function profile(User $user): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/create-profil', name: '_create')]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function createUser(
        Request                $request,
        EntityManagerInterface $em,
        FileUploader           $fileUploader,
        ParameterbagInterface  $parameterBag,
        UserPasswordHasherInterface $passwordHasher,
    ): Response
    {
        $userProfile = new User();

        $form = $this->createForm(RegistrationFormType::class, $userProfile, [
            'include_password_and_terms' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($userProfile, $plainPassword);
            $userProfile->setPassword($hashedPassword);

            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {
                $dir = $parameterBag->get('photo')['photo_profile'];
                $name = $fileUploader->upload(
                    $file,
                    $userProfile->getPseudo(),
                    $dir
                );

                // Pas besoin de supprimer l'ancien fichier pour une création
                $userProfile->setPoster($name);
            }

            $em->persist($userProfile);
            $em->flush();

            $this->addFlash('success', 'Utilisateur ' . $userProfile->getPseudo() . ' a été créé avec succès !');

            return $this->redirectToRoute('user_create');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $userProfile,
            'mode' => 'create',
        ]);
    }


    #[Route('/profile/{id}/edit', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request                $request,
        EntityManagerInterface $em,
        FileUploader           $fileUploader,
        ParameterbagInterface  $parameterBag,
        User                   $userProfile,
    ): Response
    {

        $userLogged = $this->getUser();

        if ($userLogged !== $userProfile  && !in_array('ROLE_ADMINISTRATEUR', $userLogged->getRoles())) {
            $this->addFlash('danger', 'Vous n\'avez pas accès aux modifications.');
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(RegistrationFormType::class, $userProfile, [
            'include_password_and_terms' => false,
         ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('poster_file')->getData();
            if ($file instanceof UploadedFile) {

                $dir = $parameterBag->get('photo')['photo_profile'];
                $name = $fileUploader->upload(
                    $file,
                    $userProfile->getPseudo(),
                    $dir
                );
                if ($userProfile->getPoster() && file_exists($dir . '/' . $userProfile->getPoster())) {
                    unlink($dir . '/' . $userProfile->getPoster());
                }
                $userProfile->setPoster($name);
            }

            $em->flush();
            $this->addFlash('success', 'Profil mis à jour !');

            return $this->redirectToRoute('user_profile', ['id' => $userProfile->getId()]);
        }
            return $this->render('user/edit.html.twig', [
                'form' => $form->createView(),
                'user' => $userProfile,
                'mode' => 'edit',
            ]);
    }

}