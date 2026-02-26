<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    private HttpClientInterface $httpClient;
    private string $geminiApiKey;
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient = $httpClient;
        $this->geminiApiKey = $geminiApiKey;
    }

    public function chat(string $userMessage, User $user): string
    {
        try {
            // Build the context with user information and sports domain knowledge
            $context = $this->buildContext($user);
            
            // Prepare the request payload
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $context . "\n\nUser Question: " . $userMessage
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ];

            // Make the API request
            $response = $this->httpClient->request('POST', self::GEMINI_API_URL . '?key=' . $this->geminiApiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            // Extract the response text
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            return 'I apologize, but I could not generate a response. Please try again.';

        } catch (\Exception $e) {
            return 'An error occurred while processing your request: ' . $e->getMessage();
        }
    }

    private function buildContext(User $user): string
    {
        $userRole = ucfirst($user->getRoleType());
        $userName = $user->getName();

        $context = <<<EOT
You are a friendly and knowledgeable sports management assistant for a fitness and sports application. 
You help users with their fitness goals, training plans, nutrition, and competition management.

Current User Context:
- Name: {$userName}
- Role: {$userRole}
- Application: Sports Management System

Your responsibilities:
1. Help athletes track their progress, manage workouts, and achieve their fitness goals
2. Assist coaches in creating training plans, managing athletes, and developing nutrition strategies
3. Support admins in managing users, competitions, and overall system operations

Guidelines:
- Be encouraging and motivating, especially with athletes
- Provide practical advice related to sports, fitness, and training
- Use simple, clear language
- If asked about features in the app, guide users on how to use them
- For {$userRole} users, provide role-specific guidance
- Always maintain a positive and supportive tone
- If you don't know something, be honest and suggest they contact support

EOT;

        if ($user->getRoleType() === 'athlete') {
            $context .= "\nYou are speaking with an athlete. Focus on motivation, goal-setting, workout tips, and nutrition advice.";
        } elseif ($user->getRoleType() === 'coach') {
            $context .= "\nYou are speaking with a coach. Focus on training methods, athlete management, nutrition planning, and performance tracking.";
        } elseif ($user->getRoleType() === 'admin') {
            $context .= "\nYou are speaking with an admin. Focus on system management, user management, competition organization, and best practices.";
        }

        return $context;
    }

    public function validateApiKey(): bool
    {
        return !empty($this->geminiApiKey) && strlen($this->geminiApiKey) > 10;
    }
}
