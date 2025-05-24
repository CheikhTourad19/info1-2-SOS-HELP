<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddUserForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    public function editUser(

    ): Response
    {

        return $this->redirectToRoute('app_users_admin'); //
    }
    #[Route('/admin/users/delete{id}', name: 'app_users_delete_admin')]
    public function deleteUser(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository ,$id         ): Response
    {$user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé.");
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_users_admin');
    }
    #[Route('/admin/users/reset_password{id}', name: 'app_user_reset_password_admin')]
    public function resetUser(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        $id
                                ): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé.");
        }

        $newPassword = 'changeme';
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', "Mot de passe réinitialisé pour l'utilisateur {$user->getEmail()}.");
        return $this->redirectToRoute('app_users_admin');
    }
    #[Route('/admin/users/add', name: 'app_users_add_admin')]
    public function addUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = new User();
        $form = $this->createForm(AddUserForm::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);


            $user->setRole('ROLE_MEDECIN');


            $user->setUpdatedAt(new \DateTimeImmutable());


            $entityManager->persist($user);
            $entityManager->flush();


            return $this->redirectToRoute('app_users_admin'); //
        }

        return $this->render('admin/add_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
