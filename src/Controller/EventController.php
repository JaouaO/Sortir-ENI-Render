<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sortie', name: 'event')]
final class EventController extends AbstractController
{
    #[Route('/{id}', name: '_display')]
    public function display(int $id): Response
    {
        return $this->render('event/display.html.twig',['id' => $id]);
    }
    #[Route('/creer', name: '_create')]
    public function create(): Response
    {
        return $this->render('event/edit.html.twig');
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
}
