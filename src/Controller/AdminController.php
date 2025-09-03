<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Form\UserImportType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration', name: 'admin')]
final class AdminController extends AbstractController
{
    #[Route('/interface', name: '_interface')]
    public function index(EventRepository $eventRepository, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $events = $eventRepository->findAll();


        return $this->render('admin/interface.html.twig', ['events' => $events, 'users' => $users]);
    }

    #[Route('/{id}/desactiver', name: '_desactivate')]
    public function desactivate(User $user, EntityManagerInterface $em,  MailerInterface $mailer): Response
    {

        $user->setIsActive(false);

        foreach ($user->getRegisteredEvents() as $event) {
            $event->removeRegisteredParticipant($user);
        }


        $email = (new TemplatedEmail())
            ->from(new Address('mailer@campus-eni.fr', 'ENI MAIL BOT'))
            ->to((string) $user->getEmail())
            ->subject('You have been deactivated')
            ->htmlTemplate('email/deactivate.html.twig')
            ->context([
                'user' => $user,
            ])
          ;

        $mailer->send($email);

        $em->flush();



        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été désactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/reactiver', name: '_reactivate')]
    public function reactivate(User $user, EntityManagerInterface $em): Response
    {

        $user->setIsActive(true);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été réactivé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/{id}/supprimer', name: '_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('danger', "L'utilisateur avec l'ID $id n'existe pas.");
            return $this->redirectToRoute('admin_interface');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', "L'utilisateur {$user->getName()} a bien été supprimé.");
        return $this->redirectToRoute('admin_interface');
    }

    #[Route('/import', name: '_import')]
    public function import(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserImportType::class);
        $form->handleRequest($request);

        $previewUsers = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('csvFile')->getData();
            if (!$file) {
                $this->addFlash('error', 'Fichier non trouvé.');
                return $this->redirectToRoute('admin_import');
            }

            if (($handle = fopen($file->getPathname(), 'r')) !== false) {
                $headerLine = fgets($handle);
                $headerLine = preg_replace('/\x{FEFF}/u', '', $headerLine);
                $header = array_map('trim', str_getcsv($headerLine, ';'));

                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    $data = array_map('trim', $data);

                    if (empty(array_filter($data))) {
                        continue;
                    }

                    if (count($data) !== count($header)) {
                        continue;
                    }

                    $row = array_combine($header, $data);
                    if (!$row || empty($row['email'])) {
                        continue;
                    }

                    $previewUsers[] = $row;
                }
                fclose($handle);
            }

            // Si clic sur "Importer"
            if ($request->request->has('import')) {
                foreach ($previewUsers as $row) {
                    $user = new User();
                    $user->setEmail($row['email']);
                    $user->setPseudo($row['pseudo'] ?? '');
                    $user->setName($row['name'] ?? '');
                    $user->setFirstName($row['firstName'] ?? '');
                    $user->setPhone($row['phone'] ?? '');
                    $user->setRoles(!empty($row['roles']) ? explode(',', $row['roles']) : ['ROLE_USER']);
                    $password = !empty($row['password']) ? $row['password'] : '123456';
                    $hashedPassword = $passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    $user->setIsAdmin(!empty($row['isAdmin']));
                    $user->setIsActive(!empty($row['isActive']));

                    $site = $em->getRepository(Site::class)->findOneBy(['name' => $row['site'] ?? null]);
                    if ($site) {
                        $user->setSite($site);
                    }

                    $em->persist($user);
                }
                $em->flush();

                $this->addFlash('success', 'Utilisateurs importés avec succès.');
                return $this->redirectToRoute('admin_import');
            }
        }

        return $this->render('admin/import.html.twig', [
            'form' => $form->createView(),
            'previewUsers' => $previewUsers,
        ]);
    }

}