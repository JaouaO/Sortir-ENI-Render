<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
