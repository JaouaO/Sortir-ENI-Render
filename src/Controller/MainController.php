<?php

namespace App\Controller;

use App\Form\EventType;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route(['/','/accueil'], name: 'home')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();

        // Création du formulaire (adapter EventType selon le formulaire réel)
        $form = $this->createForm(EventType::class);

        return $this->render('event/display.html.twig', [
            'events' => $events,
            'events_form' => $form->createView(),
        ]);
    }
}
