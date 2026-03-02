<?php
// src/Service/FoodCalorieService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FoodCalorieService
{
    private $httpClient;
    private $apiKey;
    private $apiUrl;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $apiUrl)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    /**
     * Analyze food from an image file
     */
    public function analyzeFood(UploadedFile $file): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'body' => [
                    'image' => fopen($file->getPathname(), 'r'),
                ],
            ]);

            $data = $response->toArray();
            
            return [
                'success' => true,
                'data' => $this->formatResponse($data)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to analyze image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze food from an image path
     */
    public function analyzeFoodFromPath(string $imagePath): array
    {
        try {
            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found');
            }

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'body' => [
                    'image' => fopen($imagePath, 'r'),
                ],
            ]);

            $data = $response->toArray();
            
            return [
                'success' => true,
                'data' => $this->formatResponse($data)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to analyze image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze food from base64 encoded image
     */
    public function analyzeFoodFromBase64(string $base64Image): array
    {
        try {
            // Decode base64 to temporary file
            $imageData = base64_decode($base64Image);
            $tempFile = tempnam(sys_get_temp_dir(), 'food_') . '.jpg';
            file_put_contents($tempFile, $imageData);

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'body' => [
                    'image' => fopen($tempFile, 'r'),
                ],
            ]);

            // Clean up temp file
            unlink($tempFile);

            $data = $response->toArray();
            
            return [
                'success' => true,
                'data' => $this->formatResponse($data)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to analyze image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format the API response
     */
    private function formatResponse(array $data): array
    {
        // Format depends on actual API response structure
        // Adjust according to Calorie Mama API documentation
        return [
            'foodName' => $data['food_name'] ?? $data['name'] ?? 'Unknown',
            'calories' => $data['calories'] ?? 0,
            'protein' => $data['protein'] ?? 0,
            'carbs' => $data['carbs'] ?? 0,
            'fat' => $data['fat'] ?? 0,
            'confidence' => $data['confidence'] ?? 0,
            'servingSize' => $data['serving_size'] ?? null,
            'servingUnit' => $data['serving_unit'] ?? null,
            'allFoods' => $data['foods'] ?? [$data] // For multiple foods detection
        ];
    }
}