<?php
<<<<<<< HEAD

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
=======
// src/Service/ChatbotService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ChatbotService
{
    private $httpClient;
    private $apiToken;

    public function __construct(HttpClientInterface $httpClient, string $apiToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $apiToken;
    }

    /**
     * Generate text response from Hugging Face model
     */
    public function generateText(string $prompt, string $model = 'gpt2'): array
    {
        // If no API token, use fallback
        if (empty($this->apiToken) || $this->apiToken === 'your_huggingface_token_here') {
            return $this->getFallbackResponse($prompt);
        }

        try {
            $response = $this->httpClient->request('POST', "https://api-inference.huggingface.co/models/{$model}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_length' => 150,
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                        'do_sample' => true,
                    ]
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $content = $response->toArray();
                
                if (isset($content[0]['generated_text'])) {
                    return [
                        'success' => true,
                        'text' => $content[0]['generated_text'],
                    ];
                }
            }
            
            // Model might be loading
            if ($statusCode === 503) {
                return [
                    'success' => false,
                    'error' => 'Model is loading, please try again in a few seconds',
                ];
            }

            return $this->getFallbackResponse($prompt);

        } catch (TransportExceptionInterface $e) {
            // Network error - use fallback
            return $this->getFallbackResponse($prompt);
        } catch (\Exception $e) {
            // Other errors - use fallback
            return $this->getFallbackResponse($prompt);
        }
    }

    /**
     * Generate speech from text using TTS
     */
    public function generateSpeech(string $text, string $language = 'en'): array
    {
        try {
            // Using a TTS model from Hugging Face
            $model = $language === 'fr' ? 'facebook/mms-tts-fra' : 'facebook/mms-tts-eng';
            
            $response = $this->httpClient->request('POST', "https://api-inference.huggingface.co/models/{$model}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                ],
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $audioContent = $response->getContent();
                
                // Save audio to file
                $filename = 'chatbot_audio_' . uniqid() . '.wav';
                $filepath = __DIR__ . '/../../public/uploads/audio/' . $filename;
                
                if (!is_dir(dirname($filepath))) {
                    mkdir(dirname($filepath), 0777, true);
                }
                
                file_put_contents($filepath, $audioContent);

                return [
                    'success' => true,
                    'audio_url' => '/uploads/audio/' . $filename,
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to generate speech',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get nutrition advice from a specialized model
     */
    public function getNutritionAdvice(string $query): array
    {
        // Using a model fine-tuned for nutrition advice
        $model = 'microsoft/DialoGPT-medium';
        
        $prompt = "As a nutrition expert, please answer this question: " . $query;
        
        return $this->generateText($prompt, $model);
    }

    /**
     * Generate meal ideas based on preferences
     */
    public function generateMealIdea(array $preferences): array
    {
        $prompt = "Generate a healthy meal idea ";
        
        if (!empty($preferences['calories'])) {
            $prompt .= "with around {$preferences['calories']} calories ";
        }
        
        if (!empty($preferences['diet'])) {
            $prompt .= "that is {$preferences['diet']} ";
        }
        
        if (!empty($preferences['cuisine'])) {
            $prompt .= "from {$preferences['cuisine']} cuisine ";
        }
        
        if (!empty($preferences['allergies'])) {
            $prompt .= "that avoids {$preferences['allergies']} ";
        }
        
        $prompt .= ". Include ingredients, nutritional info, and preparation steps.";
        
        return $this->generateText($prompt, 'gpt2');
    }

    /**
     * Translate text to different languages
     */
    public function translateText(string $text, string $targetLanguage): array
    {
        $languageCodes = [
            'fr' => 'fra',
            'es' => 'spa',
            'de' => 'deu',
            'it' => 'ita',
            'pt' => 'por',
            'ru' => 'rus',
            'zh' => 'zho',
            'ja' => 'jpn',
            'ko' => 'kor',
            'ar' => 'ara'
        ];
        
        $langCode = $languageCodes[$targetLanguage] ?? $targetLanguage;
        $model = 'Helsinki-NLP/opus-mt-en-' . $langCode;
        
        return $this->generateText($text, $model);
    }

    /**
     * Get fallback responses when API is unavailable
     */
    private function getFallbackResponse(string $prompt): array
    {
        $promptLower = strtolower($prompt);
        
        // Meal ideas
        if (strpos($promptLower, 'meal') !== false || strpos($promptLower, 'recipe') !== false || strpos($promptLower, 'eat') !== false) {
            return [
                'success' => true,
                'text' => $this->getRandomMealIdea(),
            ];
        }
        
        // Nutrition advice
        if (strpos($promptLower, 'protein') !== false) {
            return [
                'success' => true,
                'text' => "For athletes, protein intake should be around 1.6-2.2g per kg of body weight daily. Good sources include chicken, fish, eggs, dairy, legumes, and protein supplements. Spread protein intake throughout the day for optimal muscle synthesis.",
            ];
        }
        
        if (strpos($promptLower, 'calories') !== false || strpos($promptLower, 'calorie') !== false) {
            return [
                'success' => true,
                'text' => "Calorie needs vary based on activity level. For athletes: Light training: 25-30 kcal/kg/day, Moderate training: 30-35 kcal/kg/day, Heavy training: 35-40 kcal/kg/day, Very heavy: 40-45+ kcal/kg/day. Adjust based on your goals.",
            ];
        }
        
        if (strpos($promptLower, 'carb') !== false) {
            return [
                'success' => true,
                'text' => "Carbohydrates are crucial for athletic performance. Aim for 3-12g per kg of body weight depending on training intensity. Focus on complex carbs like whole grains, fruits, vegetables, and legumes for sustained energy.",
            ];
        }
        
        if (strpos($promptLower, 'fat') !== false) {
            return [
                'success' => true,
                'text' => "Healthy fats should comprise 20-35% of total calories. Focus on unsaturated fats from avocados, nuts, seeds, olive oil, and fatty fish. Fats are important for hormone function and absorbing fat-soluble vitamins.",
            ];
        }
        
        if (strpos($promptLower, 'hydrat') !== false || strpos($promptLower, 'water') !== false || strpos($promptLower, 'drink') !== false) {
            return [
                'success' => true,
                'text' => "Proper hydration is essential. Drink 2-3 hours before exercise: 500-600ml, during exercise: 150-300ml every 15-20 minutes, after exercise: 450-675ml for every 0.5kg lost. Monitor urine color - pale yellow indicates good hydration.",
            ];
        }
        
        if (strpos($promptLower, 'pre-workout') !== false || strpos($promptLower, 'before workout') !== false || strpos($promptLower, 'preworkout') !== false) {
            return [
                'success' => true,
                'text' => "Pre-workout meals should be eaten 1-4 hours before exercise. Good options: Banana with peanut butter, oatmeal with berries, whole grain toast with honey, or a smoothie with protein powder. Focus on easily digestible carbs with moderate protein.",
            ];
        }
        
        if (strpos($promptLower, 'post-workout') !== false || strpos($promptLower, 'after workout') !== false || strpos($promptLower, 'postworkout') !== false) {
            return [
                'success' => true,
                'text' => "Post-workout nutrition is crucial for recovery. Within 30-60 minutes after exercise, consume a 3:1 or 4:1 ratio of carbs to protein. Good options: Chocolate milk, protein shake with banana, grilled chicken with sweet potato, or Greek yogurt with fruit.",
            ];
        }
        
        if (strpos($promptLower, 'weight loss') !== false || strpos($promptLower, 'lose weight') !== false) {
            return [
                'success' => true,
                'text' => "For weight loss while maintaining performance: Aim for a moderate deficit of 300-500 calories below maintenance. Increase protein to 1.8-2.2g/kg body weight, focus on nutrient-dense foods, and time carbs around workouts. Never drop calories too low as it can hurt performance.",
            ];
        }
        
        if (strpos($promptLower, 'muscle gain') !== false || strpos($promptLower, 'build muscle') !== false || strpos($promptLower, 'gain muscle') !== false) {
            return [
                'success' => true,
                'text' => "For muscle gain: Aim for a slight surplus of 300-500 calories above maintenance. Consume 1.6-2.2g protein/kg body weight, adequate carbs to fuel workouts, and healthy fats. Spread meals evenly throughout the day and focus on whole foods.",
            ];
        }
        
        if (strpos($promptLower, 'breakfast') !== false) {
            return [
                'success' => true,
                'text' => "Healthy breakfast ideas for athletes: Oatmeal with berries and protein powder, Greek yogurt parfait with granola, scrambled eggs with spinach and whole grain toast, or a protein smoothie with banana and peanut butter. Aim for balanced macros to fuel your morning.",
            ];
        }
        
        if (strpos($promptLower, 'lunch') !== false) {
            return [
                'success' => true,
                'text' => "Nutritious lunch options: Grilled chicken salad with quinoa, turkey and avocado wrap, tuna sandwich on whole grain bread, or a burrito bowl with brown rice, black beans, and lean protein. Include veggies and healthy fats for sustained energy.",
            ];
        }
        
        if (strpos($promptLower, 'dinner') !== false) {
            return [
                'success' => true,
                'text' => "Balanced dinner ideas: Salmon with roasted vegetables and sweet potato, lean beef stir-fry with brown rice, baked chicken breast with quinoa and steamed broccoli, or lentil soup with whole grain bread. Focus on protein and complex carbs for recovery.",
            ];
        }
        
        if (strpos($promptLower, 'snack') !== false) {
            return [
                'success' => true,
                'text' => "Healthy snack options: Apple with peanut butter, Greek yogurt with berries, protein bar, trail mix, cottage cheese with fruit, hummus with vegetable sticks, or a handful of almonds. Snacks can help maintain energy between meals.",
            ];
        }
        
        if (strpos($promptLower, 'vegetarian') !== false || strpos($promptLower, 'vegan') !== false) {
            return [
                'success' => true,
                'text' => "Plant-based protein sources: Lentils, chickpeas, quinoa, tofu, tempeh, edamame, seitan, and plant-based protein powders. Combine different sources throughout the day to ensure complete amino acid profile. Don't forget B12 supplementation for vegans.",
            ];
        }
        
        // Default response
        return [
            'success' => true,
            'text' => "I'm your AI nutrition assistant. I can help with meal ideas, nutrition advice, diet planning, and more. Try asking specific questions like:\n\n" .
                      "• 'Give me a high-protein meal idea'\n" .
                      "• 'What should I eat before a workout?'\n" .
                      "• 'How much protein do I need?'\n" .
                      "• 'Create a meal plan for weight loss'\n" .
                      "• 'Best foods for recovery after training'\n" .
                      "• 'Healthy breakfast options for athletes'",
        ];
    }

    /**
     * Get random meal idea
     */
    private function getRandomMealIdea(): string
    {
        $meals = [
            "Grilled chicken salad with quinoa, mixed greens, cherry tomatoes, cucumber, and a light lemon vinaigrette. (450 calories, 35g protein, 40g carbs, 15g fat)",
            "Salmon with roasted sweet potato and steamed broccoli. Season salmon with herbs and lemon. (500 calories, 38g protein, 45g carbs, 20g fat)",
            "Turkey and avocado wrap with whole wheat tortilla, lean turkey breast, avocado slices, lettuce, tomato, and mustard. (400 calories, 30g protein, 35g carbs, 15g fat)",
            "Greek yogurt bowl with mixed berries, honey, and granola. Add chia seeds for extra omega-3s. (350 calories, 25g protein, 45g carbs, 10g fat)",
            "Lean beef stir-fry with bell peppers, broccoli, snap peas, and brown rice. Use low-sodium soy sauce and ginger. (480 calories, 40g protein, 50g carbs, 12g fat)",
            "Protein smoothie: banana, whey protein, almond milk, spinach, peanut butter, and ice. Perfect post-workout. (350 calories, 30g protein, 35g carbs, 12g fat)",
            "Quinoa bowl with black beans, corn, avocado, salsa, and grilled chicken. Top with Greek yogurt instead of sour cream. (520 calories, 38g protein, 60g carbs, 18g fat)",
            "Egg white omelette with spinach, mushrooms, bell peppers, and feta cheese. Serve with whole grain toast. (380 calories, 32g protein, 25g carbs, 16g fat)",
            "Tuna salad with chickpeas, cucumber, red onion, and olive oil lemon dressing. Serve over mixed greens. (420 calories, 35g protein, 30g carbs, 18g fat)",
            "Overnight oats with protein powder, almond milk, berries, and chia seeds. Prepare the night before for an easy breakfast. (400 calories, 28g protein, 50g carbs, 12g fat)",
            "Baked cod with roasted asparagus and wild rice. Season with garlic, lemon, and herbs. (440 calories, 42g protein, 35g carbs, 12g fat)",
            "Chickpea and spinach curry with brown rice. Use coconut milk, tomatoes, and Indian spices. (420 calories, 15g protein, 65g carbs, 14g fat)",
            "Turkey chili with kidney beans, tomatoes, and bell peppers. Top with avocado and serve with whole grain crackers. (450 calories, 35g protein, 50g carbs, 12g fat)",
            "Shrimp and vegetable skewers with quinoa tabbouleh. Marinate shrimp in lemon and herbs. (380 calories, 32g protein, 40g carbs, 10g fat)",
            "Peanut butter and banana protein pancakes made with oats, egg whites, and protein powder. (420 calories, 35g protein, 45g carbs, 15g fat)",
        ];
        
        return $meals[array_rand($meals)];
    }
}
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
