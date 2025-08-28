<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function desactivate(User $user, EntityManagerInterface $em): Response
    {

        $user->setIsActive(false);
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
}
