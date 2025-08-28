<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route(['/', '/accueil'], name: 'home')]
    public function index(EventRepository $eventRepository, SiteRepository $siteRepository, EntityManagerInterface $em, Request $request): Response
    {

        $sites = $siteRepository->findAll();

        $user = $this->getUser(); // l'utilisateur connecté 
        $registered = $request->query->get('registered');
        $notRegistered = $request->query->get('notRegistered');
        $siteId = $request->query->get('site');
        $dateStart = $request->query->get('dateStart');
        $dateEnd = $request->query->get('dateEnd');
        $search = $request->query->get('search');
        $outingPast = $request->query->get('outingPast');
        $organizer = $request->query->get('organizer');

        $nbRegisteredByEvent = [];
        $isUserEventRegistered = [];

        if (!$siteId) {
            $qb = $eventRepository->createQueryBuilder('e');
        } else {
            // Filter on site + date 
            $qb = $eventRepository->createQueryBuilder('e')
                ->andWhere('e.site = :site')
                ->setParameter('site', $siteId);
        }

        // Filter on date 
        if ($dateStart) {
            $qb->andWhere('e.startDateTime >= :dateStart')
                ->setParameter('dateStart', new \DateTime($dateStart));
        }
        if ($dateEnd) {
            $qb->andWhere('e.startDateTime <= :dateEnd')
                ->setParameter('dateEnd', new \DateTime($dateEnd));
        }

        // Filter on name's event 
        if ($search) {
            $qb->andWhere('LOWER(e.name) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // filter on events where user is registered 
        if ($registered && $user) {
            $qb->join('e.registeredParticipants', 'u')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }

        // filter on events where user is not registered 
        if ($notRegistered && $user) {
            $qb->leftJoin('e.registeredParticipants', 'u', 'WITH', 'u.id = :userId')
                ->andWhere('u.id IS NULL')
                ->setParameter('userId', $user->getId());
        }

        // Filter on past events 
        if ($outingPast) {
            $today = new \DateTime(); // date et heure actuelles 
            $qb->andWhere('e.startDateTime <= :today')
                ->setParameter('today', $today);
        }

        //Filter events where user is organizer 
        if ($organizer && $user) {
            $qb->andWhere('e.organizer = :organizer')
                ->setParameter('organizer', $user->getId());
        }

        if ($registered && !$notRegistered) {
            // Events where user participate 
            $qb->join('e.registeredParticipants', 'p')
                ->andWhere('p = :user')
                ->setParameter('user', $user);
        } elseif ($notRegistered && !$registered) {
            $qb->leftJoin('e.registeredParticipants', 'p')
                ->andWhere('p != :user OR p.id IS NULL')
                ->setParameter('user', $user);
            // Events where user don't participate 
//            $subQb = $em->getRepository(Event::class)->createQueryBuilder('e2') 
//                ->select('e2.id') 
//                ->join('e2.registeredParticipants', 'p2') 
//                ->where('p2 = :user'); 
// 
//            $qb->andWhere($qb->expr()->notIn('e.id', $subQb->getDQL())) 
//                ->setParameter('user', $user); 
        }
        // if both checkboxes or none, any filter applied 

        $events = $qb->getQuery()->getResult();

        foreach ($events as $event) {

            //get id of the event 
            $eventId = $event->getId();
            //count nb of participants : 
            $nbRegisteredByEvent [$eventId] = $event->getRegisteredParticipants()->count();

            //check user is event participant "inscrit" 
            if ($event->getRegisteredParticipants()->contains($user)){
                $isUserEventRegistered[$eventId] = true;
            } else {
                $isUserEventRegistered[$eventId] = false;
            }
        }


        return $this->render('main/index.html.twig', [
            'events' => $events,
            'sites' => $sites,
            'nbRegisteredByEvent' => $nbRegisteredByEvent,
            'isUserEventRegistered' => $isUserEventRegistered,
        ]);
    }
} 
 

 