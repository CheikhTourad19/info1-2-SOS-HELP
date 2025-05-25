<?php

namespace App\Controller;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use App\Entity\Comment;
use App\Entity\Message;
use App\Entity\Post;
use App\Entity\PostDocument;
use App\Entity\PostImage;
use App\Entity\Reply;
use App\Entity\User;
use App\Form\CommentTypeForm;
use App\Form\PostTypeForm;
use App\Form\RegistrationFormTypeForm;
use App\Form\ReplyTypeForm;
use App\Repository\PostRepository;
use App\Repository\MessageRepository;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use DateTime;

final class MedecinController extends AbstractController
{
    #[Route('/medecin', name: 'app_home_medecin')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('medecin/index.html.twig', [
            'posts' => $posts
        ]);
    }
    #[Route('/medecin/profile', name: 'app_profile_medecin')]
    public function profil(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(RegistrationFormTypeForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traiter le mot de passe uniquement s'il a été saisi
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }



            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');

            return $this->redirectToRoute('app_home_medecin');
        }

        return $this->render('medecin/profile.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    #[Route('/medecin/post/new', name: 'app_post_medecin')]
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

                foreach ($images as $image) {
                    try {
                        if ($image instanceof UploadedFile) {
                            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $filename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();


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

                foreach ($documents as $doc) {
                    try {
                        if ($doc instanceof UploadedFile) {
                            $originalFilename = pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                            $safeFilename = $slugger->slug($originalFilename);
                            $filename = $safeFilename . '-' . uniqid() . '.' . $doc->guessExtension();


                            // Déplacer le fichier vers le dossier uploads
                            $doc->move($uploadDir, $filename);

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
                return $this->redirectToRoute('app_home_medecin');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement des fichiers: ' . $e->getMessage());
            }
        }

        return $this->render('medecin/post.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('medecin/post/{id}', name: 'app_post_show')]
    public function show(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
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
            $email = (new Email())
                ->from('Forum-Medical@med.com')
                ->to($post->getAuthor()->getEmail())
                ->subject('Nouveau commentaire')
                ->text('Vous avez Une nouveau commentaire a votre post');

            $mailer->send($email);
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

        return $this->render('medecin/post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentForm,
            'replyForms' => $replyForms,
        ]);
    }

    #[Route('medecin/reply/add/{commentId}', name: 'app_reply_add', methods: ['POST'])]
    public function addReply(
        int $commentId,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
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
            $email = (new Email())
                ->from('Forum-Medical@med.com')
                ->to($comment->getAuthor()->getEmail())
                ->subject('Nouvelle reponse')
                ->text('Vous avez Une nouvelle reponse a votre commentaie:');

            $mailer->send($email);
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

    #[Route('/medecin/chatbot', name: 'app_chatbot_medecin')]
    public function chatbot(): Response
    {
        return $this->render('medecin/chatbot.html.twig');
    }
    #[Route('/medecin/messages', name: 'app_message_medecin')]
    public function messages(MessageRepository $messageRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $conversations = $messageRepository->findLatestConversations($user);

        // Récupérer tous les médecins comme contacts potentiels
        $availableDoctors = $userRepository->findAvailableContacts($user);

        return $this->render('medecin/messages.html.twig', [
            'conversations' => $conversations,
            'availableDoctors' => $availableDoctors
        ]);
    }

    // Ajouter une nouvelle route pour démarrer une conversation
    #[Route('/medecin/start-conversation/{id}', name: 'app_start_conversation')]
    public function startConversation(User $user): Response
    {
        // Rediriger vers la page de conversation avec l'utilisateur sélectionné
        return $this->redirectToRoute('app_conversation_medecin', ['id' => $user->getId()]);
    }

    // Ajoutons également une route pour afficher une conversation spécifique
    #[Route('/medecin/messages/{id}', name: 'app_conversation_medecin')]
    public function showConversation(
        User $otherUser,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        // Récupération de tous les messages entre les deux utilisateurs
        $messages = $messageRepository->createQueryBuilder('m')
            ->where('(m.sender = :currentUser AND m.receiver = :otherUser) OR (m.sender = :otherUser AND m.receiver = :currentUser)')
            ->setParameter('currentUser', $currentUser)
            ->setParameter('otherUser', $otherUser)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Marquer les messages non lus comme lus
        foreach ($messages as $message) {
            if ($message->getReceiver() === $currentUser && !$message->isRead()) {
                $message->setIsRead(true);
                $entityManager->persist($message);
            }
        }
        $entityManager->flush();

        return $this->render('medecin/conversation.html.twig', [
            'messages' => $messages,
            'otherUser' => $otherUser
        ]);
    }
    #[Route('/medecin/send-message/{id}', name: 'app_send_message', methods: ['POST'])]
    public function sendMessage(
        User $receiver,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('content');

        if (!empty($content)) {
            $message = new Message();
            $message->setSender($currentUser);
            $message->setReceiver($receiver);
            $message->setContent($content);
            $message->setSentAt(new DateTime());
            $message->setIsRead(false);

            $entityManager->persist($message);
            $entityManager->flush();
            $email = (new Email())
                ->from('Forum-Medical@med.com')
                ->to($receiver->getEmail())
                ->subject('Test Email')
                ->text('Vous avez Un nouveau message provenant de :' . $currentUser->getFirstName());

            $mailer->send($email);
        }

        return $this->redirectToRoute('app_conversation_medecin', ['id' => $receiver->getId()]);
    }
    #[Route('/medecin/profil/{id}', name: 'app_bio')]
    public function bio(User $user, PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy(['author' => $user]);

        return $this->render('medecin/bio.html.twig', [
            'posts' => $posts,
            'user' => $user,
        ]);
    }
    #[Route('/medecin/search', name: 'app_search_medecin')]
    public function search(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('medecin/search.html.twig', [
            'users' => $users,
        ]);
    }
    #[Route('/medecin/pubs', name: 'app_pubs_medecin', methods: ['GET'])]
    public function pubs(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findBy(['author' => $this->getUser()]);

        return $this->render('medecin/pubs.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/medecin/pubs/delete', name: 'app_pubs_delete', methods: ['POST'])]
    public function deletepubs(EntityManagerInterface $entityManager, Request $request): Response
    {
        $id = $request->request->get('id');
        $post = $entityManager->getRepository(Post::class)->find($id);

        if ($post && $post->getAuthor() === $this->getUser()) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimée avec succès.');
        }

        return $this->redirectToRoute('app_pubs');
    }
    #[Route('/medecin/contact', name: 'app_contact_medecin', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $role = $request->request->get('role');
            $email = $request->request->get('email');
            $issueType = $request->request->get('issue-type');
            $message = $request->request->get('message');

            $emailMessage = (new Email())
                ->from(new Address('Forum-Medical@med.com', 'Forum Medical'))
                ->to(
                    new Address('bentaherdaly123@gmail.com', 'Admin 4')
                ) //adresse admin
                ->subject("Demande technique de $name ($role) - $issueType")
                ->text(
                    "Nom : $name\n" .
                    "Rôle : $role\n" .
                    "Email : $email\n" .
                    "Type de problème : $issueType\n\n" .
                    "Message :\n$message"
                )
                ->html("
        <div style='font-family: Arial, sans-serif;'>
            <h2 style='color: #4f46e5;'>Nouvelle demande technique</h2>
            <p><strong>Nom:</strong> $name</p>
            <p><strong>Rôle:</strong> $role</p>
            <p><strong>Email:</strong> <a href='mailto:$email'>$email</a></p>
            <p><strong>Type de problème:</strong> $issueType</p>
            <p><strong>Message:</strong></p>
            <blockquote style='background-color: #f9f9f9; padding: 10px; border-left: 4px solid #4f46e5;'>
                $message
            </blockquote>
            <hr style='margin-top: 30px;'>
            <p style='font-size: 12px; color: #888;'>Forum Medical – Formulaire de contact medecin</p>
        </div>
    ");

            try {
                $mailer->send($emailMessage);
                $this->addFlash('success', 'Votre message a bien été envoyé.');
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi du message.');
            }

            return $this->redirectToRoute('app_contact_medecin');
        }

        return $this->render('medecin/ContactUs.html.twig');
    }

}
