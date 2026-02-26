<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\NutritionPlanRepository;
use App\Repository\MealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        NutritionPlanRepository $nutritionPlanRepository,
        MealRepository $mealRepository
    ): Response
    {
        // Get the current user
        $user = $this->getUser();

        // Count athletes (users with ROLE_ATHLETE)
        $athletesCount = $userRepository->count(['roleType' => 'athlete']);

        // Count nutrition plans of current coach
        $nutritionPlansCount = $nutritionPlanRepository->count(['coach' => $user]);

        // Count meals of current coach
        $mealsCount = $mealRepository->count(['coach' => $user]);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'athletesCount' => $athletesCount,
            'nutritionPlansCount' => $nutritionPlansCount,
            'mealsCount' => $mealsCount,
        ]);
    }
}