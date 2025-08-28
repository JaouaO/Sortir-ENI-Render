<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie', name: 'event')]
final class EventController extends AbstractController
{
    #[Route('/creer', name: '_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Une nouvelle sortie a été créée');
            return $this->redirectToRoute('home');
            #return $this->redirectToRoute('event_display', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig',[
            'event_form' => $form,
            'mode' => 'create'
        ]);
    }

     #[Route('/{id}/modifier', name: '_edit', requirements: ['id' => '\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Une sortie à été modifiée avec succès');

           return $this->render('event_display',['id' => $event->getId()]);
        }


        return $this->render('event/edit.html.twig',[
            'event_form' => $form,
            'mode' => 'edit'
        ]);
    }
    #[Route('/{id}/annuler', name: '_cancel')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function cancel(int $id): Response
    {
        return $this->render('event/cancel.html.twig',['id' => $id]);
    }

    #[Route('/{id}', name: '_display')]
    public function display(Event $event): Response
    {
        return $this->render('event/display.html.twig',['event' => $event]);
    }
}
