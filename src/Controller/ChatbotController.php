<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $userMessage = $data['message'] ?? '';

            if (empty($userMessage)) {
                return new JsonResponse(['success' => false, 'message' => 'Message is required'], 400);
            }

            if (strlen($userMessage) > 2000) {
                return new JsonResponse(['success' => false, 'message' => 'Message is too long (max 2000 characters)'], 400);
            }

            // Get the authenticated user
            $user = $this->getUser();

            // Get the chatbot response
            $response = $chatbotService->chat($userMessage, $user);

            return new JsonResponse([
                'success' => true,
                'message' => 'Message processed successfully',
                'response' => $response
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/chatbot', name: 'chatbot')]
    public function chatbot(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('chatbot/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
