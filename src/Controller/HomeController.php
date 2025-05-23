<?php

namespace App\Controller;

use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user=$this->getUser();
        if($user){
            if ($user->getRole() === 'ROLE_MEDECIN') {
                return $this->redirectToRoute('app_home_medecin');
            }
            if ($user->getRole() === 'ROLE_PATIENT') {
                return $this->redirectToRoute('app_home_patient');
            }
            if ($user->getRole() === 'ROLE_ADMIN') {
                return $this->redirectToRoute('app_home_admin');
            }
        }
        return $this->redirectToRoute('app_login');
    }
}
