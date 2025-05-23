<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostDocument;
use App\Entity\PostImage;
use App\Entity\Reply;
use App\Form\CommentTypeForm;
use App\Form\PostTypeForm;
use App\Form\ReplyTypeForm;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class PatientController extends AbstractController
{
    #[Route('/patient', name: 'app_home_patient')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('patient/index.html.twig', [
            'posts' => $posts
        ]);
    }
    #[Route('/patient/profile', name: 'app_profile_patient')]
    public function profil(): Response
    {
        return $this->render('patient/profile.html.twig', [
        ]);
    }
    #[Route('/patient/post/new', name: 'app_post_patient')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        KernelInterface $kernel
    ): Response {
        $post = new Post();
        $form = $this->createForm(PostTypeForm::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());

            try {
                // Créer le dossier uploads s'il n'existe pas
                $uploadDir = $kernel->getProjectDir() . '/public/uploads';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Handle image uploads
                $images = $form->get('images')->getData();
                $this->addFlash('info', 'Nombre d\'images à télécharger: ' . count($images));

                foreach ($images as $image) {
                    try {
                        if ($image instanceof UploadedFile) {
                            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $filename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                            $this->addFlash('info', 'Traitement de l\'image: ' . $originalFilename);

                            // Déplacer le fichier vers le dossier uploads
                            $image->move($uploadDir, $filename);

                            $this->addFlash('success', 'Image téléchargée avec succès: ' . $filename);
                            $postImage = new PostImage();
                            $postImage->setFilename($filename);
                            $postImage->setPost($post);
                            $entityManager->persist($postImage);
                        } else {
                            $this->addFlash('error', 'Le fichier n\'est pas une instance de UploadedFile');
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement d\'une image: ' . $e->getMessage());
                    }
                }

                // Handle PDF uploads
                $documents = $form->get('documents')->getData();
                $this->addFlash('info', 'Nombre de documents à télécharger: ' . count($documents));

                foreach ($documents as $doc) {
                    try {
                        if ($doc instanceof UploadedFile) {
                            $originalFilename = pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $filename = $safeFilename . '-' . uniqid() . '.' . $doc->guessExtension();

                            $this->addFlash('info', 'Traitement du document: ' . $originalFilename);

                            // Déplacer le fichier vers le dossier uploads
                            $doc->move($uploadDir, $filename);

                            $this->addFlash('success', 'Document téléchargé avec succès: ' . $filename);
                            $postDoc = new PostDocument();
                            $postDoc->setFilename($filename);
                            $postDoc->setPost($post);
                            $entityManager->persist($postDoc);
                        } else {
                            $this->addFlash('error', 'Le fichier n\'est pas une instance de UploadedFile');
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors du téléchargement d\'un document: ' . $e->getMessage());
                    }
                }

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', 'Votre post a été créé avec succès!');
                return $this->redirectToRoute('app_home_patient');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement des fichiers: ' . $e->getMessage());
            }
        }

        return $this->render('patient/post.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('patient/post/{id}', name: 'app_post_show')]
    public function show(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Créer le formulaire de commentaire
        $comment = new Comment();
        $commentForm = $this->createForm(CommentTypeForm::class, $comment);
        $commentForm->handleRequest($request);

        // Traiter la soumission du formulaire de commentaire
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAuthor($user);
            $comment->setPost($post);

            $entityManager->persist($comment);
            $entityManager->flush();

            // Rediriger pour éviter la resoumission du formulaire
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        // Créer un formulaire de réponse pour chaque commentaire
        $replyForms = [];
        foreach ($post->getComments() as $existingComment) {
            $reply = new Reply();
            $replyForm = $this->createForm(ReplyTypeForm::class, $reply, [
                'action' => $this->generateUrl('app_reply_add', [
                    'commentId' => $existingComment->getId(),
                ]),
            ]);
            $replyForms[$existingComment->getId()] = $replyForm->createView();
        }

        return $this->render('patient/post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm,
            'replyForms' => $replyForms,
        ]);
    }

    #[Route('patient/reply/add/{commentId}', name: 'app_reply_add', methods: ['POST'])]
    public function addReply(
        int $commentId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le commentaire
        $comment = $entityManager->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire non trouvé');
        }

        // Créer et traiter le formulaire de réponse
        $reply = new Reply();
        $form = $this->createForm(ReplyTypeForm::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setAuthor($user);
            $reply->setComment($comment);

            $entityManager->persist($reply);
            $entityManager->flush();

            // Rediriger vers le post avec un fragment pour le commentaire
            return $this->redirectToRoute('app_post_show', [
                'id' => $comment->getPost()->getId(),
                '_fragment' => 'comment-' . $comment->getId(),
            ]);
        }

        // En cas d'erreur, rediriger vers le post
        return $this->redirectToRoute('app_post_show', [
            'id' => $comment->getPost()->getId(),
        ]);
    }



}
