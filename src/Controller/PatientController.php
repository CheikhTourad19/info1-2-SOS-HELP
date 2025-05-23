<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PatientController extends AbstractController
{
    #[Route('/patient', name: 'app_home_patient')]
    public function index(): Response
    {
        return $this->render('patient/index.html.twig', [
        ]);
    }
    #[Route('/patient/profile', name: 'app_profile_patient')]
    public function profil(): Response
    {
        return $this->render('patient/index.html.twig', [
        ]);
    }
    #[Route('/patient/post', name: 'app_post_patient')]
    public function post(): Response
    {
        return $this->render('patient/index.html.twig', [
        ]);
    }
}
