<?php

namespace App\Controller;

use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route(['/','/accueil'], name: 'home')]
    public function index(EventRepository $eventRepository, SiteRepository $siteRepository, EntityManagerInterface $em, Request $request): Response
    {
        //Recherche par site
        $sites = $siteRepository->findAll();
        $siteId = $request->query->get('site');
        if ($siteId) {
            $events = $eventRepository->findBy(['site' => $siteId]);
        } else {
            $events = $eventRepository->findAll();
        }




        return $this->render('main/index.html.twig', [
            'events' => $events,
            'sites' => $sites
        ]);
    }
}
