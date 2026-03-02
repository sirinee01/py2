<?php
// src/Controller/ChatbotController.php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/coach/chatbot')]
class ChatbotController extends AbstractController
{
    #[Route('', name: 'app_coach_chatbot')]
    public function index(SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Initialize chat history if not exists
        if (!$session->has('chat_history')) {
            $session->set('chat_history', [
                [
                    'role' => 'assistant',
                    'content' => 'Hello! I\'m your AI nutrition assistant. How can I help you today? You can ask me about meal planning, nutrition advice, recipes, or even have me generate meal ideas for your athletes!',
                    'timestamp' => time()
                ]
            ]);
        }
        
        return $this->render('coach/chatbot.html.twig', [
            'chat_history' => $session->get('chat_history'),
        ]);
    }

    #[Route('/send', name: 'app_coach_chatbot_send', methods: ['POST'])]
    public function send(Request $request, ChatbotService $chatbot, SessionInterface $session): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $message = $request->request->get('message');
        $messageType = $request->request->get('type', 'text'); // text, meal_idea, nutrition_advice
        
        if (!$message) {
            return $this->json(['error' => 'Message is required'], 400);
        }

        // Add user message to history
        $history = $session->get('chat_history', []);
        $history[] = [
            'role' => 'user',
            'content' => $message,
            'timestamp' => time()
        ];

        // Get AI response based on type
        $response = match($messageType) {
            'meal_idea' => $chatbot->generateMealIdea(['query' => $message]),
            'nutrition_advice' => $chatbot->getNutritionAdvice($message),
            default => $chatbot->generateText($message)
        };

        if (!$response['success']) {
            // Fallback response
            $aiResponse = "I'm having trouble connecting to my AI services right now. Here's a sample meal idea: Grilled chicken salad with quinoa, mixed greens, cherry tomatoes, and a light vinaigrette. Approximately 450 calories, 35g protein, 40g carbs, 15g fat.";
        } else {
            $aiResponse = $response['text'] ?? "I understand you're asking about: " . $message . ". As a nutrition assistant, I recommend consulting with a qualified nutritionist for personalized advice.";
        }

        // Add AI response to history
        $history[] = [
            'role' => 'assistant',
            'content' => $aiResponse,
            'timestamp' => time()
        ];

        // Keep only last 20 messages to prevent session bloat
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        $session->set('chat_history', $history);

        return $this->json([
            'success' => true,
            'response' => $aiResponse,
            'history' => $history
        ]);
    }

    #[Route('/clear', name: 'app_coach_chatbot_clear')]
    public function clear(SessionInterface $session): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $session->remove('chat_history');
        
        return $this->json(['success' => true]);
    }

    #[Route('/generate-meal', name: 'app_coach_chatbot_generate_meal', methods: ['POST'])]
    public function generateMeal(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $preferences = [
            'calories' => $request->request->get('calories'),
            'diet' => $request->request->get('diet'),
            'cuisine' => $request->request->get('cuisine'),
            'allergies' => $request->request->get('allergies'),
        ];

        $response = $chatbot->generateMealIdea($preferences);

        return $this->json($response);
    }

    #[Route('/text-to-speech', name: 'app_coach_chatbot_tts', methods: ['POST'])]
    public function textToSpeech(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $text = $request->request->get('text');
        $language = $request->request->get('language', 'en');

        $response = $chatbot->generateSpeech($text, $language);

        return $this->json($response);
    }
}