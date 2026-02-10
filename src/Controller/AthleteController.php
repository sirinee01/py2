<?php

namespace App\Controller;

use App\Repository\NutritionPlanRepository;
use App\Repository\MealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AthleteController extends AbstractController
{
    #[Route('/athlete/dashboard', name: 'app_athlete_dashboard')]
    public function dashboard(NutritionPlanRepository $nutritionPlanRepo, MealRepository $mealRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        // For now, show all nutrition plans and meals
        // Later you can assign specific plans to athletes
        $nutritionPlans = $nutritionPlanRepo->findAll();
        $meals = $mealRepo->findAll();
        
        return $this->render('athlete/dashboard.html.twig', [
            'nutritionPlans' => $nutritionPlans,
            'meals' => $meals,
        ]);
    }

    #[Route('/athlete/nutrition-plans', name: 'app_athlete_nutrition_plans')]
    public function nutritionPlans(NutritionPlanRepository $nutritionPlanRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $nutritionPlans = $nutritionPlanRepo->findAll();
        
        return $this->render('athlete/nutrition_plans.html.twig', [
            'nutritionPlans' => $nutritionPlans,
        ]);
    }

    #[Route('/athlete/meals', name: 'app_athlete_meals')]
    public function meals(MealRepository $mealRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $meals = $mealRepo->findAll();
        
        return $this->render('athlete/meals.html.twig', [
            'meals' => $meals,
        ]);
    }

    #[Route('/athlete/nutrition-plan/{id}', name: 'app_athlete_nutrition_plan_view')]
    public function viewNutritionPlan(int $id, NutritionPlanRepository $nutritionPlanRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $nutritionPlan = $nutritionPlanRepo->find($id);
        
        if (!$nutritionPlan) {
            throw $this->createNotFoundException('Nutrition plan not found');
        }
        
        return $this->render('athlete/nutrition_plan_view.html.twig', [
            'nutritionPlan' => $nutritionPlan,
        ]);
    }
}