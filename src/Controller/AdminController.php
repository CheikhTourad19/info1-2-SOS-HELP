<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
//    #[Route('/admin', name: 'app_admin')]
//    public function index(): Response
//    {
//        return $this->render('admin/index.html.twig', [
//            'controller_name' => 'AdminController',
//        ]);
//    }
    #[Route('/admin/home', name: 'app_home_admin')]
    public function home(): Response
    {
        return $this->render('admin/index.html.twig',
            []);
    }
    #[Route('/admin/users', name: 'app_users_admin')]
    public function users(UserRepository $userRepository): Response
    {   $patients=$userRepository->findBy(['role'=>'ROLE_PATIENT']);
        $medecins=$userRepository->findBy(['role'=>'ROLE_MEDECIN']);
        return $this->render('admin/users.html.twig', ['patients'=>$patients,
            'medecins'=>$medecins,]);
    }
    #[Route('//admin/users/edit{id}', name: 'app_users_edit_admin')]
    public function editUser(): Response
    {
        return $this->render('admin/index.html.twig',
            []);
    }
    #[Route('/admin/users/delete{id}', name: 'app_users_delete_admin')]
    public function deleteUser(): Response
    {
        return $this->render('admin/index.html.twig');
    }
    #[Route('/admin/users/reset_password{id}', name: 'app_user_reset_password_admin')]
    public function resetUser(): Response
    {
        return $this->render('admin/index.html.twig');
    }
    #[Route('/admin/users/add', name: 'app_users_add_admin')]
    public function addUser(): Response
    {
        return $this->render('admin/index.html.twig');
    }

}
