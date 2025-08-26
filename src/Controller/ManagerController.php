<?php

namespace App\Controller;

use App\Repository\CityRepository;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gestion', name: 'manager')]
final class ManagerController extends AbstractController
{
    #[Route('/villes', name: '_cities', methods: ['GET'])]
    public function cities(CityRepository $cityRepository, Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        $cities = $cityRepository->findCities($searchTerm);

        return $this->render('manager/cities.html.twig', [
            'cities' => $cities,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/sites', name: '_places')]
    public function places(): Response
    {
        return $this->render('manager/places.html.twig');
    }
}
