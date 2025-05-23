<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostTypeForm;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PatientController extends AbstractController
{
    #[Route('/patient', name: 'app_home_patient')]
    public function index(PostRepository $postRepository): Response
    {
        $posts=$postRepository->findAll();
        return $this->render('patient/index.html.twig', [
            'posts'=>$posts
        ]);
    }
    #[Route('/patient/profile', name: 'app_profile_patient')]
    public function profil(): Response
    {
        return $this->render('patient/profile.html.twig', [
        ]);
    }
    #[Route('/patient/post/new', name: 'app_post_patient')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostTypeForm::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir l'auteur comme l'utilisateur connecté
            $post->setAuthor($this->getUser());

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Votre post a été créé avec succès!');

            return $this->redirectToRoute('app_home_patient');
        }

        return $this->render('patient/post.html.twig', [
            'form' => $form->createView(),
        ]);
    }
        #[Route('/patient/post/{id}', name: 'app_view_post_patient')]
        public function viewPost(int $id , PostRepository $postRepository): Response
        {
            $postRepository->find($id);
            return $this->render('patient/viewpost.html.twig');
        }
        #[Route('/patient/comment', name: 'app_comment_patient')]
        public function comment (){

        }

}
