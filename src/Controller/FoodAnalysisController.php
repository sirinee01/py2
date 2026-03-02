<?php
// src/Controller/FoodAnalysisController.php

namespace App\Controller;

use App\Service\FoodCalorieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/food-analysis')]
#[IsGranted('ROLE_USER')]
class FoodAnalysisController extends AbstractController
{
    #[Route('/upload', name: 'app_food_analysis_upload', methods: ['POST'])]
    public function upload(Request $request, FoodCalorieService $foodService): JsonResponse
    {
        $file = $request->files->get('image');
        
        if (!$file) {
            return $this->json([
                'success' => false,
                'error' => 'No image uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid file type. Only JPG and PNG are allowed.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'error' => 'File too large. Maximum size is 5MB.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $foodService->analyzeFood($file);

        if (!$result['success']) {
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($result);
    }

    #[Route('/analyze-base64', name: 'app_food_analysis_base64', methods: ['POST'])]
    public function analyzeBase64(Request $request, FoodCalorieService $foodService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;

        if (!$base64Image) {
            return $this->json([
                'success' => false,
                'error' => 'No image data provided'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Remove data:image/jpeg;base64, prefix if present
        if (strpos($base64Image, 'base64,') !== false) {
            $base64Image = substr($base64Image, strpos($base64Image, 'base64,') + 7);
        }

        $result = $foodService->analyzeFoodFromBase64($base64Image);

        if (!$result['success']) {
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($result);
    }
}