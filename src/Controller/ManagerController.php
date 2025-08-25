<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gestion', name: 'manager')]
final class ManagerController extends AbstractController
{
    #[Route('/villes', name: '_cities')]
    public function cities(): Response
    {
        return $this->render('manager/cities.html.twig');
    }
    #[Route('/sites', name: '_places')]
    public function places(): Response
    {
        return $this->render('manager/places.html.twig');
    }
}
