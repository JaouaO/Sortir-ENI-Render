<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    #[Route('/{id}/modifier', name: '_edit')]
    public function edit(int $id): Response
    {
        return $this->render('event/edit.html.twig',['id' => $id]);
    }
    #[Route('/{id}/annuler', name: '_cancel')]
    public function cancel(int $id): Response
    {
        return $this->render('event/cancel.html.twig',['id' => $id]);
    }
    #[Route('/{id}', name: '_display')]
    public function display(int $id): Response
    {
        return $this->render('event/display.html.twig',['id' => $id]);
    }
}
