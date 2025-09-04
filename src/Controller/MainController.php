<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\SiteRepository;
use App\Service\EventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $em;
    private EventService $eventService;

    public function __construct(EventRepository $eventRepository, EventService $eventService, EntityManagerInterface $em)
    {
        $this->eventRepository = $eventRepository;
        $this->eventService = $eventService;
        $this->em = $em;
    }


    #[Route(['/', '/accueil'], name: 'home')]
    public function index(Request $request): Response
    {
        $user = $this->getUser(); //connected user

        if(!$user){
            $user= new  User();
        }

        $today = new \DateTimeImmutable();

        $nbRegisteredByEvent = [];
        $isUserEventRegistered = [];

        $siteRepository = $this->em->getRepository(Site::class);
        $sites = $siteRepository->findAll();

        $params = $request->query->all();
        //call filters set in EventService
        $filters = $this->eventService->eventFilter($params, $this->getUser());
        //call requests in Event Repo
        $events = $this->eventRepository->queryFilters($filters);


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

            $state = $this->eventService->determinestate($event, $nbRegisteredByEvent, $eventId, $today);
            $event->setState($state);
            $this->em->persist($event);
            $this->em->flush();
        }


        return $this->render('main/index.html.twig', [
            'user' => $user,
            'events' => $events,
            'sites' => $sites,
            'nbRegisteredByEvent' => $nbRegisteredByEvent,
            'isUserEventRegistered' => $isUserEventRegistered,
        ]);
    }

} 
 

 