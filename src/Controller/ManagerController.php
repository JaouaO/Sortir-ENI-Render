<?php

namespace App\Controller;

use App\Entity\City;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/gestion', name: 'manager')]
final class ManagerController extends AbstractController
{
    #[Route('/villes', name: '_cities', methods: ['GET'])]
    //#[IsGranted('ROLE_ADMINISTRATEUR')]
    public function cities(CityRepository $cityRepository, Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        $cities = $cityRepository->findCities($searchTerm);

        return $this->render('manager/cities.html.twig', [
            'cities' => $cities,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/creer', name: '_create')]
    public function createCity(
        CityRepository $cityRepository,
        Request $request,
        EntityManagerInterface $em,
    ): Response {

            $city = new City();

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');
            $cp = $request->request->get('cp');

            $city->setName($town);
            $city->setPostCode($cp);

            $em->persist($city);
            $em->flush();

            $this->addFlash('success', "La ville {$city->getName()} a été ajouté");
            return $this->redirectToRoute('manager_cities');
        }

        $cities = $cityRepository->findAll();

        return $this->render('manager/update-city.html.twig', [
            'mode' => 'create',
            'cities' => $cities,
            'city' => $city, // Pour pré-remplir le formulaire
        ]);
    }



    #[Route('/{id}/modifier', name: '_update', requirements: ['id' => '\d+'])]
    public function updateCity(
        CityRepository $cityRepository,
        Request $request,
        City $city,
        EntityManagerInterface $em,
        ): Response {

        if ($request->isMethod('POST')) {
            $town = $request->request->get('town');
            $cp = $request->request->get('cp');

                $city->setName($town);
                $city->setPostCode($cp);

                $em->persist($city);
                $em->flush();

                $this->addFlash('success', "La ville {$city->getName()} a été modifié");
                return $this->redirectToRoute('manager_cities');
            }

        $cities = $cityRepository->findAll();

        return $this->render('manager/update-city.html.twig', [
            'mode' => 'update',
            'cities' => $cities,
            'city' => $city, // Pour pré-remplir le formulaire
        ]);
    }



    #[Route('//{id}/supprimer', name: '_delete', requirements: ['id' => '\d+'])]
    public function deleteCity(int $id, EntityManagerInterface $em): Response {

        $city = $em->getRepository(City::class)->find($id);

        if(!$city){
            $this->addFlash('danger', 'La ville n\'existe pas');
            return $this->redirectToRoute('manager_cities');
        }

        $em->remove($city);
        $em->flush();

        $this->addFlash('success', "La ville {$city->getName()} a été supprimé");
        return $this->redirectToRoute('manager_cities');
    }


    #[Route('/sites', name: '_places')]
    //#[IsGranted('ROLE_ADMINISTRATEUR')]
    public function places(): Response
    {
        return $this->render('manager/places.html.twig');
    }
}
