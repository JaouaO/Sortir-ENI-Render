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


        $user = $this->getUser(); // l'utilisateur connecté
        $registered = $request->query->get('registered');
        $notRegistered = $request->query->get('notRegistered');

        //Recherche par site
        $sites = $siteRepository->findAll();
        $siteId = $request->query->get('site');

        $dateStart = $request->query->get('dateStart');
        $dateEnd = $request->query->get('dateEnd');
        $search = $request->query->get('search');
        $outingPast = $request->query->get('outingPast');


        if (!$siteId) {
            // Pas de filtre sur site, mais filtre sur la date si définie
            $qb = $eventRepository->createQueryBuilder('e');
        } else {
            // Filtre sur site + date
            $qb = $eventRepository->createQueryBuilder('e')
                ->andWhere('e.site = :site')
                ->setParameter('site', $siteId);
        }

        // Ajout du filtre date si les dates sont fournies
        if ($dateStart) {
            $qb->andWhere('e.startDateTime >= :dateStart')
                ->setParameter('dateStart', new \DateTime($dateStart));
        }
        if ($dateEnd) {
            $qb->andWhere('e.startDateTime <= :dateEnd')
                ->setParameter('dateEnd', new \DateTime($dateEnd));
        }

        if ($search) {
            $qb->andWhere('LOWER(e.name) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        if ($registered && $user) {
            // filtre événements où l'utilisateur connecté est inscrit
            $qb->join('e.registeredParticipants', 'u')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }

        if ($notRegistered && $user) {
            $qb->leftJoin('e.registeredParticipants', 'u', 'WITH', 'u.id = :userId')
                ->andWhere('u.id IS NULL')
                ->setParameter('userId', $user->getId());
        }

        if ($outingPast) {
            $today = new \DateTime(); // date et heure actuelles
            $qb->andWhere('e.startDateTime <= :today')
                ->setParameter('today', $today);
        }

        $events = $qb->getQuery()->getResult();


        return $this->render('main/index.html.twig', [
            'events' => $events,
            'sites' => $sites
        ]);
    }
}
