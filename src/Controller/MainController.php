<?php

namespace App\Controller;

use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route(['/','/accueil'], name: 'home')]
    public function index(EventRepository $eventRepository, SiteRepository $siteRepository, EntityManagerInterface $em): Response
    {
        $events = $eventRepository->findAll();
        $sites = $siteRepository->findAll();

        $em->persist();
        $em->flush();

        return $this->render('event/display.html.twig', [
            'events' => $events,
            'sites' => $sites
        ]);
    }
}
