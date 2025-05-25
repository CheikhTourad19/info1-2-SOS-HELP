<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddUserForm;
use App\Form\UpdateUserTypeForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
    #[Route('/admin/users/edit/{id}', name: 'app_users_edit_admin')]
    public function editUser(
        Request $request,
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        $id
    ): Response {
        $user=$em->getRepository(User::class)->find($id);
        $form = $this->createForm(UpdateUserTypeForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer le mot de passe s’il est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Vérifie si la spécialité est requise pour le rôle Médecin
            if ($user->getRole() === 'ROLE_MEDECIN' && empty($user->getSpecialty())) {
                $form->get('specialty')->addError(new FormError('La spécialité est requise pour les médecins.'));
            } else {
                $user->setUpdatedAt(new \DateTimeImmutable());
                $em->flush();

                $this->addFlash('success', 'Utilisateur modifié avec succès.');
                return $this->redirectToRoute('app_users_admin');
            }
        }

        return $this->render('admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
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
        MailerInterface $mailer,
        $id
    ): Response {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé.");
        }

        $newPassword = 'changeme';
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        // ✅ Send email to the user
        $email = (new Email())
            ->from(new Address('Forum-Medical@med.com', 'Forum Medical'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("
            <p>Bonjour {$user->getFirstname()},</p>
            <p>Votre mot de passe a été réinitialisé par l'administrateur.</p>
            <p>Votre nouveau mot de passe est : <strong>$newPassword</strong></p>
            <p>Merci de vous connecter et de le modifier dès que possible.</p>
            <br>
            <p style='font-size: 12px; color: #888;'>Forum Medical</p>
        ");

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l’envoi de l’email : ' . $e->getMessage());
        }

        $this->addFlash('success', "Mot de passe réinitialisé pour l'utilisateur {$user->getEmail()} et email envoyé.");
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

            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                ));

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
