<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    #[Route('', name: 'app_messages_index')]
    public function index(
        ConversationRepository $conversationRepo, 
        UserRepository $userRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Get all conversations for this user
        $conversations = $conversationRepo->findByUser($user);
        
        // Get users that can be messaged based on role
        $availableUsers = [];
        
        if (in_array('ROLE_COACH', $user->getRoles())) {
            // Coach can message all athletes
            $availableUsers = $userRepo->findBy(['roleType' => 'athlete']);
        } else {
            // Athlete can message their assigned coach
            $assignedPlan = $user->getAssignedNutritionPlan();
            if ($assignedPlan && $assignedPlan->getCoach()) {
                $availableUsers = [$assignedPlan->getCoach()];
            }
        }
        
        // Filter out users that already have conversations
        $usersWithConversations = [];
        foreach ($conversations as $conversation) {
            $other = $conversation->getOtherParticipant($user);
            if ($other) {
                $usersWithConversations[] = $other->getId();
            }
        }
        
        $availableUsers = array_filter($availableUsers, function($availableUser) use ($usersWithConversations, $user) {
            return !in_array($availableUser->getId(), $usersWithConversations) && $availableUser->getId() !== $user->getId();
        });

        // Choose template based on user role
        if (in_array('ROLE_COACH', $user->getRoles())) {
            // Coach template with sidebar
            return $this->render('message/index.html.twig', [
                'conversations' => $conversations,
                'availableUsers' => $availableUsers,
                'user' => $user,
            ]);
        } else {
            // Athlete template with navbar
            return $this->render('message/athlete_index.html.twig', [
                'conversations' => $conversations,
                'availableUsers' => $availableUsers,
                'user' => $user,
            ]);
        }
    }

    #[Route('/conversation/{id}', name: 'app_messages_conversation')]
    public function conversation(
        Conversation $conversation,
        MessageRepository $messageRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Security: user must be a participant
        if (!$conversation->getParticipants()->contains($user)) {
            throw $this->createAccessDeniedException();
        }

        // Mark all messages as read
        $messageRepo->markAllAsRead($conversation, $user);

        $otherParticipant = $conversation->getOtherParticipant($user);

        // Choose template based on user role
        if (in_array('ROLE_COACH', $user->getRoles())) {
            // Coach template with sidebar
            return $this->render('message/conversation.html.twig', [
                'conversation' => $conversation,
                'otherParticipant' => $otherParticipant,
                'messages' => $conversation->getMessages(),
            ]);
        } else {
            // Athlete template with navbar
            return $this->render('message/athlete_conversation.html.twig', [
                'conversation' => $conversation,
                'otherParticipant' => $otherParticipant,
                'messages' => $conversation->getMessages(),
            ]);
        }
    }

    #[Route('/start/{userId}', name: 'app_messages_start')]
    public function start(
        int $userId,
        UserRepository $userRepo,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Fetch the user manually
        $otherUser = $userRepo->find($userId);
        
        if (!$otherUser) {
            throw $this->createNotFoundException('User not found');
        }

        // Prevent chatting with self
        if ($user === $otherUser) {
            $this->addFlash('error', 'You cannot start a conversation with yourself.');
            return $this->redirectToRoute('app_messages_index');
        }

        // Look for existing conversation
        $conversation = $conversationRepo->findOneByParticipants($user, $otherUser);

        if (!$conversation) {
            $conversation = new Conversation();
            
            // First persist the conversation
            $entityManager->persist($conversation);
            
            // Then add participants
            $conversation->addParticipant($user);
            $conversation->addParticipant($otherUser);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Conversation started successfully!');
        }

        return $this->redirectToRoute('app_messages_conversation', ['id' => $conversation->getId()]);
    }

    #[Route('/send', name: 'app_messages_send', methods: ['POST'])]
    public function send(
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $conversationId = $data['conversationId'] ?? null;
        $content = trim($data['content'] ?? '');

        if (!$conversationId || !$content) {
            return $this->json(['error' => 'Missing data'], 400);
        }

        $conversation = $conversationRepo->find($conversationId);
        if (!$conversation || !$conversation->getParticipants()->contains($user)) {
            return $this->json(['error' => 'Invalid conversation'], 403);
        }

        $message = new Message();
        $message->setSender($user);
        $message->setConversation($conversation);
        $message->setContent($content);
        $entityManager->persist($message);

        $conversation->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('H:i'),
                'sender' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ]
            ]
        ]);
    }

    #[Route('/unread-count', name: 'app_messages_unread_count')]
    public function unreadCount(MessageRepository $messageRepo): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $count = $messageRepo->countUnreadForUser($user);
        return $this->json(['count' => $count]);
    }
}