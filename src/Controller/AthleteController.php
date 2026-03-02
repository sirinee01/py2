<?php
// src/Controller/AthleteController.php

namespace App\Controller;

use App\Entity\MealConsumption;
use App\Entity\WaterIntake;
use App\Entity\Progress;
use App\Entity\CustomMealLog;
use App\Repository\NutritionPlanRepository;
use App\Repository\MealRepository;
use App\Service\PdfGeneratorService;  // <<< AJOUTEZ CETTE LIGNE
use App\Repository\MealConsumptionRepository;
use App\Repository\WaterIntakeRepository;
use App\Repository\ProgressRepository;
use App\Repository\CustomMealLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/athlete')]
#[IsGranted('ROLE_ATHLETE')]
class AthleteController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/dashboard', name: 'app_athlete_dashboard')]
    public function dashboard(
        NutritionPlanRepository $nutritionPlanRepo,
        MealConsumptionRepository $mealConsumptionRepo,
        WaterIntakeRepository $waterIntakeRepo,
        ProgressRepository $progressRepo,
        CustomMealLogRepository $customMealRepo
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Get assigned nutrition plan
        $assignedPlan = $user->getAssignedNutritionPlan();
        
        $todaysMeals = [];
        $dailyTarget = [
            'calories' => 2000,
            'protein' => 150,
            'carbs' => 200,
            'fat' => 50,
            'water' => 2.5
        ];
        
        $todaysStats = [
            'caloriesConsumed' => 0,
            'proteinConsumed' => 0,
            'carbsConsumed' => 0,
            'fatConsumed' => 0,
            'waterIntake' => 0
        ];
        
        $waterLogs = [];
        $goalProgress = 0;
        $startWeight = null;
        $currentWeight = null;
        $targetWeight = null;
        $userGoal = 'Weight Loss';
        
        if ($assignedPlan) {
            // Get today's meals from the plan
            $todaysMealsArray = $assignedPlan->getTodaysMeals();
            
            // Get all consumed meals for today
            $consumedMeals = [];
            foreach ($user->getMealConsumptions() as $consumption) {
                $today = new \DateTime('today');
                $tomorrow = new \DateTime('tomorrow');
                if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                    $consumedMeals[$consumption->getMeal()->getId()] = [
                        'servings' => $consumption->getServings() ?? 1,
                        'consumptionId' => $consumption->getId()
                    ];
                }
            }
            
            // Organize meals by meal time
            foreach ($todaysMealsArray as $meal) {
                $mealTime = $meal->getMealTime();
                if (!isset($todaysMeals[$mealTime])) {
                    $todaysMeals[$mealTime] = [];
                }
                
                $isConsumed = isset($consumedMeals[$meal->getId()]);
                
                $mealArray = [
                    'id' => $meal->getId(),
                    'name' => $meal->getName(),
                    'description' => $meal->getDescription(),
                    'calories' => $meal->getCalories(),
                    'protein' => $meal->getProtein(),
                    'carbs' => $meal->getCarbs(),
                    'fat' => $meal->getFat(),
                    'image' => $meal->getImage(),
                    'consumed' => $isConsumed,
                    'servings' => $isConsumed ? $consumedMeals[$meal->getId()]['servings'] : 1,
                    'type' => 'plan',
                    'mealTime' => $mealTime,
                    'dayOfWeek' => $meal->getDayOfWeek()
                ];
                $todaysMeals[$mealTime][] = $mealArray;
            }
            
            // Get custom meals for today
            foreach ($user->getCustomMealLogs() as $customMeal) {
                $today = new \DateTime('today');
                $tomorrow = new \DateTime('tomorrow');
                if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                    $mealTime = $customMeal->getMealTime();
                    if (!isset($todaysMeals[$mealTime])) {
                        $todaysMeals[$mealTime] = [];
                    }
                    $todaysMeals[$mealTime][] = [
                        'type' => 'custom',
                        'id' => $customMeal->getId(),
                        'name' => $customMeal->getName(),
                        'description' => $customMeal->getDescription(),
                        'calories' => $customMeal->getCalories(),
                        'protein' => $customMeal->getProtein() ?? 0,
                        'carbs' => $customMeal->getCarbs() ?? 0,
                        'fat' => $customMeal->getFat() ?? 0,
                        'reason' => $customMeal->getReason(),
                        'time' => $customMeal->getConsumedAt(),
                        'mealTime' => $mealTime,
                        'consumed' => true,
                        'image' => null
                    ];
                }
            }
            
            // Sort meals within each meal time
            foreach ($todaysMeals as $mealTime => &$meals) {
                usort($meals, function($a, $b) {
                    // If both have time property (custom meals), sort by time
                    if (isset($a['time']) && isset($b['time'])) {
                        return $a['time'] <=> $b['time'];
                    }
                    // If one has time and other doesn't, put the one with time first
                    if (isset($a['time']) && !isset($b['time'])) return -1;
                    if (!isset($a['time']) && isset($b['time'])) return 1;
                    // Otherwise sort by meal time order
                    $order = ['breakfast' => 1, 'lunch' => 2, 'dinner' => 3, 'snack' => 4];
                    return ($order[$a['mealTime'] ?? 'snack'] ?? 5) <=> ($order[$b['mealTime'] ?? 'snack'] ?? 5);
                });
            }
            
            // Calculate daily targets from the plan
            $dailyTarget = $assignedPlan->calculateDailyTargets();
        }
        
        // Get today's stats
        $todaysStats = [
            'caloriesConsumed' => $user->getTodaysCalories(),
            'proteinConsumed' => $user->getTodaysProtein(),
            'carbsConsumed' => $user->getTodaysCarbs(),
            'fatConsumed' => $user->getTodaysFat(),
            'waterIntake' => $user->getTodaysTotalWater()
        ];
        
        // Get water logs for today
        $waterIntakes = $user->getTodaysWaterIntakes();
        foreach ($waterIntakes as $intake) {
            $waterLogs[] = [
                'amount' => $intake->getAmount(),
                'time' => $intake->getConsumedAt()
            ];
        }
        
        // Get progress data
        $latestProgress = $user->getLatestProgress();
        $onboardingProgress = $user->getOnboardingProgress();
        
        if ($latestProgress) {
            $currentWeight = $latestProgress->getWeight();
            $targetWeight = $latestProgress->getTargetWeight();
            $userGoal = $latestProgress->getPrimaryGoal() ?? 'Weight Loss';
            
            if ($onboardingProgress && $onboardingProgress->getWeight()) {
                $startWeight = $onboardingProgress->getWeight();
                
                // Calculate goal progress
                if ($startWeight && $currentWeight && $targetWeight) {
                    if ($targetWeight > $startWeight) {
                        // Weight gain goal
                        $totalToGain = $targetWeight - $startWeight;
                        $gained = $currentWeight - $startWeight;
                        $goalProgress = $totalToGain > 0 ? min(100, round(($gained / $totalToGain) * 100, 1)) : 0;
                    } else {
                        // Weight loss goal
                        $totalToLose = $startWeight - $targetWeight;
                        $lost = $startWeight - $currentWeight;
                        $goalProgress = $totalToLose > 0 ? min(100, round(($lost / $totalToLose) * 100, 1)) : 0;
                    }
                }
            }
        }
        
        return $this->render('athlete/dashboard.html.twig', [
            'user' => $user,
            'assignedPlan' => $assignedPlan,
            'todaysMeals' => $todaysMeals,
            'dailyTarget' => $dailyTarget,
            'todaysStats' => $todaysStats,
            'waterLogs' => $waterLogs,
            'goalProgress' => $goalProgress,
            'startWeight' => $startWeight,
            'currentWeight' => $currentWeight,
            'targetWeight' => $targetWeight,
            'userGoal' => $userGoal,
            'mealTimes' => ['breakfast', 'lunch', 'dinner', 'snack']
        ]);
    }

    #[Route('/meal/{id}/toggle', name: 'app_athlete_meal_toggle', methods: ['POST'])]
    public function toggleMealConsumption(
        int $id,
        MealRepository $mealRepo,
        Request $request
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $meal = $mealRepo->find($id);
        if (!$meal) {
            return $this->json(['success' => false, 'error' => 'Meal not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        $consumed = $data['consumed'] ?? true;
        $servings = $data['servings'] ?? 1;
        
        // Check if already consumed today
        $existingConsumption = null;
        foreach ($user->getMealConsumptions() as $consumption) {
            if ($consumption->getMeal() === $meal && 
                $consumption->getConsumedAt() > new \DateTime('today')) {
                $existingConsumption = $consumption;
                break;
            }
        }
        
        if ($consumed && !$existingConsumption) {
            // Add consumption
            $consumption = new MealConsumption();
            $consumption->setUser($user);
            $consumption->setMeal($meal);
            $consumption->setCompleted(true);
            $consumption->setServings($servings);
            $this->entityManager->persist($consumption);
        } elseif (!$consumed && $existingConsumption) {
            // Remove consumption
            $this->entityManager->remove($existingConsumption);
        } elseif ($consumed && $existingConsumption && $existingConsumption->getServings() != $servings) {
            // Update servings
            $existingConsumption->setServings($servings);
        }
        
        $this->entityManager->flush();
        
        // Calculate target values
        $assignedPlan = $user->getAssignedNutritionPlan();
        $targetCalories = $assignedPlan ? $assignedPlan->getTargetCalories() ?? 2000 : 2000;
        $targetProtein = $assignedPlan ? $assignedPlan->getTargetProtein() ?? 150 : 150;
        $targetCarbs = $assignedPlan ? $assignedPlan->getTargetCarbs() ?? 200 : 200;
        $targetFat = $assignedPlan ? $assignedPlan->getTargetFat() ?? 50 : 50;
        
        // Return updated stats
        return $this->json([
            'success' => true,
            'stats' => [
                'calories' => [
                    'consumed' => $user->getTodaysCalories(),
                    'percentage' => $targetCalories > 0 ? min(100, round(($user->getTodaysCalories() / $targetCalories) * 100)) : 0
                ],
                'protein' => [
                    'consumed' => $user->getTodaysProtein(),
                    'percentage' => $targetProtein > 0 ? min(100, round(($user->getTodaysProtein() / $targetProtein) * 100)) : 0
                ],
                'carbs' => [
                    'consumed' => $user->getTodaysCarbs(),
                    'percentage' => $targetCarbs > 0 ? min(100, round(($user->getTodaysCarbs() / $targetCarbs) * 100)) : 0
                ],
                'fat' => [
                    'consumed' => $user->getTodaysFat(),
                    'percentage' => $targetFat > 0 ? min(100, round(($user->getTodaysFat() / $targetFat) * 100)) : 0
                ]
            ]
        ]);
    }

    #[Route('/custom-meal/add', name: 'app_athlete_custom_meal_add', methods: ['POST'])]
    public function addCustomMeal(
        Request $request
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        $customMeal = new CustomMealLog();
        $customMeal->setUser($user);
        $customMeal->setName($data['name'] ?? 'Custom Meal');
        $customMeal->setDescription($data['description'] ?? null);
        $customMeal->setCalories($data['calories'] ?? 0);
        $customMeal->setProtein($data['protein'] ?? null);
        $customMeal->setCarbs($data['carbs'] ?? null);
        $customMeal->setFat($data['fat'] ?? null);
        $customMeal->setMealTime($data['mealTime'] ?? 'snack');
        $customMeal->setReason($data['reason'] ?? null);
        
        if (!empty($data['consumedAt'])) {
            $customMeal->setConsumedAt(new \DateTime($data['consumedAt']));
        }
        
        $this->entityManager->persist($customMeal);
        $this->entityManager->flush();
        
        // Calculate target values
        $assignedPlan = $user->getAssignedNutritionPlan();
        $targetCalories = $assignedPlan ? $assignedPlan->getTargetCalories() ?? 2000 : 2000;
        $targetProtein = $assignedPlan ? $assignedPlan->getTargetProtein() ?? 150 : 150;
        $targetCarbs = $assignedPlan ? $assignedPlan->getTargetCarbs() ?? 200 : 200;
        $targetFat = $assignedPlan ? $assignedPlan->getTargetFat() ?? 50 : 50;
        
        return $this->json([
            'success' => true,
            'meal' => [
                'id' => $customMeal->getId(),
                'name' => $customMeal->getName(),
                'calories' => $customMeal->getCalories(),
                'protein' => $customMeal->getProtein(),
                'carbs' => $customMeal->getCarbs(),
                'fat' => $customMeal->getFat(),
                'mealTime' => $customMeal->getMealTime(),
                'time' => $customMeal->getConsumedAt()->format('H:i')
            ],
            'stats' => [
                'calories' => [
                    'consumed' => $user->getTodaysCalories(),
                    'percentage' => $targetCalories > 0 ? min(100, round(($user->getTodaysCalories() / $targetCalories) * 100)) : 0
                ],
                'protein' => [
                    'consumed' => $user->getTodaysProtein(),
                    'percentage' => $targetProtein > 0 ? min(100, round(($user->getTodaysProtein() / $targetProtein) * 100)) : 0
                ],
                'carbs' => [
                    'consumed' => $user->getTodaysCarbs(),
                    'percentage' => $targetCarbs > 0 ? min(100, round(($user->getTodaysCarbs() / $targetCarbs) * 100)) : 0
                ],
                'fat' => [
                    'consumed' => $user->getTodaysFat(),
                    'percentage' => $targetFat > 0 ? min(100, round(($user->getTodaysFat() / $targetFat) * 100)) : 0
                ]
            ]
        ]);
    }

    #[Route('/custom-meal/{id}/delete', name: 'app_athlete_custom_meal_delete', methods: ['POST'])]
    public function deleteCustomMeal(
        int $id,
        CustomMealLogRepository $customMealRepo
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $customMeal = $customMealRepo->find($id);
        
        if (!$customMeal || $customMeal->getUser() !== $user) {
            return $this->json(['success' => false, 'error' => 'Custom meal not found'], 404);
        }
        
        $this->entityManager->remove($customMeal);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/water/add', name: 'app_athlete_water_add', methods: ['POST'])]
    public function addWaterIntake(
        Request $request
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? 0.25;
        
        $waterIntake = new WaterIntake();
        $waterIntake->setUser($user);
        $waterIntake->setAmount($amount);
        $waterIntake->setUnit('L');
        
        $this->entityManager->persist($waterIntake);
        $this->entityManager->flush();
        
        $totalWater = $user->getTodaysTotalWater();
        $targetWater = $user->getAssignedNutritionPlan()?->getDailyWaterIntake() ?? 2.5;
        
        return $this->json([
            'success' => true,
            'totalWater' => $totalWater,
            'percentage' => $targetWater > 0 ? min(100, round(($totalWater / $targetWater) * 100)) : 0,
            'stats' => [
                'water' => [
                    'consumed' => $totalWater,
                    'percentage' => $targetWater > 0 ? min(100, round(($totalWater / $targetWater) * 100)) : 0
                ]
            ]
        ]);
    }

    #[Route('/progress', name: 'app_athlete_progress')]
    public function progress(ProgressRepository $progressRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $progressEntries = $progressRepo->findBy(
            ['user' => $user],
            ['recordedAt' => 'ASC']
        );
        
        // Prepare chart data
        $weightLabels = [];
        $weightData = [];
        $calorieLabels = [];
        $calorieData = [];
        $waterLabels = [];
        $waterData = [];
        
        foreach ($progressEntries as $entry) {
            $date = $entry->getRecordedAt()->format('M d');
            $weightLabels[] = $date;
            $weightData[] = $entry->getWeight();
            
            $calorieLabels[] = $date;
            $calorieData[] = $entry->getDailyCalorieIntake();
            
            $waterLabels[] = $date;
            $waterData[] = $entry->getDailyWaterIntake();
        }
        
        // Get current stats
        $latestProgress = $user->getLatestProgress();
        $onboardingProgress = $user->getOnboardingProgress();
        
        $currentWeight = $latestProgress?->getWeight();
        $targetWeight = $latestProgress?->getTargetWeight();
        $startWeight = $onboardingProgress?->getWeight();
        $bodyFat = $latestProgress?->getBodyFatPercentage();
        
        // Calculate BMI
        $bmi = null;
        $bmiCategory = null;
        if ($currentWeight && $latestProgress?->getHeight()) {
            $heightInMeters = $latestProgress->getHeight() / 100;
            $bmi = round($currentWeight / ($heightInMeters * $heightInMeters), 1);
            
            $bmiCategory = match(true) {
                $bmi < 18.5 => 'Underweight',
                $bmi < 25 => 'Normal weight',
                $bmi < 30 => 'Overweight',
                default => 'Obese'
            };
        }
        
        // Calculate TDEE
        $tdee = $latestProgress?->getTdee();
        
        // Calculate goal progress
        $goalProgress = 0;
        $weightChange = null;
        $bodyFatChange = null;
        
        if ($startWeight && $currentWeight && $targetWeight) {
            if ($targetWeight > $startWeight) {
                // Weight gain goal
                $totalToGain = $targetWeight - $startWeight;
                $gained = $currentWeight - $startWeight;
                $goalProgress = $totalToGain > 0 ? min(100, round(($gained / $totalToGain) * 100, 1)) : 0;
            } else {
                // Weight loss goal
                $totalToLose = $startWeight - $targetWeight;
                $lost = $startWeight - $currentWeight;
                $goalProgress = $totalToLose > 0 ? min(100, round(($lost / $totalToLose) * 100, 1)) : 0;
            }
        }
        
        // Calculate weight change
        if (count($progressEntries) >= 2) {
            $lastWeek = $progressEntries[count($progressEntries) - 2] ?? null;
            if ($lastWeek && $lastWeek->getWeight() && $currentWeight) {
                $weightChange = round($currentWeight - $lastWeek->getWeight(), 1);
            }
        }
        
        return $this->render('athlete/progress.html.twig', [
            'progressEntries' => $progressEntries,
            'weightLabels' => $weightLabels,
            'weightData' => $weightData,
            'calorieLabels' => $calorieLabels,
            'calorieData' => $calorieData,
            'waterLabels' => $waterLabels,
            'waterData' => $waterData,
            'currentWeight' => $currentWeight,
            'startWeight' => $startWeight,
            'targetWeight' => $targetWeight,
            'bodyFat' => $bodyFat,
            'bmi' => $bmi,
            'bmiCategory' => $bmiCategory,
            'tdee' => $tdee,
            'goalProgress' => $goalProgress,
            'weightChange' => $weightChange
        ]);
    }

    #[Route('/progress/add', name: 'app_athlete_progress_add', methods: ['POST'])]
    public function addProgress(
        Request $request
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setWeight($data['weight'] ?? null);
        $progress->setBodyFatPercentage($data['bodyFat'] ?? null);
        $progress->setMuscleMass($data['muscleMass'] ?? null);
        $progress->setDailyCalorieIntake($data['calories'] ?? null);
        $progress->setDailyWaterIntake($data['water'] ?? null);
        $progress->setActivityLevel($data['activityLevel'] ?? null);
        
        // Set measurements if provided
        $measurements = [];
        if (!empty($data['waist'])) $measurements['waist'] = $data['waist'];
        if (!empty($data['chest'])) $measurements['chest'] = $data['chest'];
        if (!empty($data['hips'])) $measurements['hips'] = $data['hips'];
        if (!empty($data['arms'])) $measurements['arms'] = $data['arms'];
        $progress->setMeasurements($measurements);
        
        // Check if this is the first entry
        $existingEntries = $user->getProgressEntries()->count();
        if ($existingEntries === 0) {
            $progress->setIsInitialOnboarding(true);
        }
        
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/progress/{id}/delete', name: 'app_athlete_progress_delete', methods: ['POST'])]
    public function deleteProgress(
        int $id,
        ProgressRepository $progressRepo
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $progress = $progressRepo->find($id);
        
        if (!$progress || $progress->getUser() !== $user) {
            return $this->json(['success' => false, 'error' => 'Progress entry not found'], 404);
        }
        
        $this->entityManager->remove($progress);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/meal/log', name: 'app_athlete_meal_log', methods: ['POST'])]
    public function logMeal(
        Request $request,
        MealRepository $mealRepo
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        $mealId = $data['mealId'] ?? null;
        $servings = $data['servings'] ?? 1;
        
        if (!$mealId) {
            return $this->json(['success' => false, 'error' => 'Meal ID required'], 400);
        }
        
        $meal = $mealRepo->find($mealId);
        if (!$meal) {
            return $this->json(['success' => false, 'error' => 'Meal not found'], 404);
        }
        
        // Check if already logged today
        $alreadyLogged = false;
        foreach ($user->getMealConsumptions() as $consumption) {
            if ($consumption->getMeal() === $meal && 
                $consumption->getConsumedAt() > new \DateTime('today')) {
                $alreadyLogged = true;
                $consumption->setServings($servings);
                break;
            }
        }
        
        if (!$alreadyLogged) {
            $consumption = new MealConsumption();
            $consumption->setUser($user);
            $consumption->setMeal($meal);
            $consumption->setServings($servings);
            $this->entityManager->persist($consumption);
        }
        
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/nutrition-plan/{id}', name: 'app_athlete_nutrition_plan_view')]
    public function viewNutritionPlan(int $id, NutritionPlanRepository $nutritionPlanRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $nutritionPlan = $nutritionPlanRepo->find($id);
        
        if (!$nutritionPlan) {
            throw $this->createNotFoundException('Nutrition plan not found');
        }
        
        // Try to get meals through the relationship first
        $meals = $nutritionPlan->getMeals();
        $mealCount = $meals->count();
        
        // If no meals found through relationship, try manual query
        if ($mealCount === 0) {
            $query = $this->entityManager->createQuery(
                'SELECT m FROM App\Entity\Meal m
                JOIN m.nutritionPlans np
                WHERE np.id = :planId'
            )->setParameter('planId', $id);
            
            $mealsArray = $query->getResult();
            $meals = new \Doctrine\Common\Collections\ArrayCollection($mealsArray);
            $mealCount = count($mealsArray);
        }
        
        // Check if this plan is assigned to the user
        $isAssigned = $user->getAssignedNutritionPlan() && $user->getAssignedNutritionPlan()->getId() === $nutritionPlan->getId();
        
        // Get all consumed meals for today
        $consumedMeals = [];
        foreach ($user->getMealConsumptions() as $consumption) {
            $today = new \DateTime('today');
            $tomorrow = new \DateTime('tomorrow');
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $consumedMeals[$consumption->getMeal()->getId()] = [
                    'servings' => $consumption->getServings() ?? 1,
                    'consumptionId' => $consumption->getId()
                ];
            }
        }
        
        $mealsWithStatus = [];
        foreach ($meals as $meal) {
            $isConsumed = isset($consumedMeals[$meal->getId()]);
            $mealsWithStatus[] = [
                'meal' => $meal,
                'consumed' => $isConsumed,
                'servings' => $isConsumed ? $consumedMeals[$meal->getId()]['servings'] : 1,
                'consumptionId' => $isConsumed ? $consumedMeals[$meal->getId()]['consumptionId'] : null
            ];
        }
        
        // Calculate plan progress
        $planProgress = 0;
        $totalMeals = count($mealsWithStatus);
        if ($totalMeals > 0) {
            $consumedCount = count(array_filter($mealsWithStatus, fn($item) => $item['consumed']));
            $planProgress = round(($consumedCount / $totalMeals) * 100);
        }
        
        return $this->render('athlete/nutrition_plan_view.html.twig', [
            'nutritionPlan' => $nutritionPlan,
            'mealsWithStatus' => $mealsWithStatus,
            'isAssigned' => $isAssigned,
            'planProgress' => $planProgress,
            'debug' => [
                'mealCount' => $mealCount,
                'planId' => $id,
                'relationshipWorked' => $nutritionPlan->getMeals()->count() > 0
            ]
        ]);
    }

    #[Route('/food-analysis', name: 'app_athlete_food_analysis')]
    public function foodAnalysis(): Response
    {
        return $this->render('food_analysis/index.html.twig');
    }

    #[Route('/food-analysis-demo', name: 'app_athlete_food_analysis_demo')]
public function foodAnalysisDemo(): Response
{
    return $this->render('athlete/food_analysis_demo.html.twig');
}

#[Route('/download-report', name: 'app_athlete_download_report')]
public function downloadReport(PdfGeneratorService $pdfService): Response
{
    /** @var \App\Entity\User $user */
    $user = $this->getUser();
    
    // Get assigned nutrition plan
    $assignedPlan = $user->getAssignedNutritionPlan();
    
    // Calculate percentages
    $dailyTarget = $assignedPlan ? $assignedPlan->calculateDailyTargets() : [
        'calories' => 2000,
        'protein' => 150,
        'carbs' => 200,
        'fat' => 50,
        'water' => 2.5
    ];
    
    $todaysStats = [
        'caloriesConsumed' => $user->getTodaysCalories(),
        'proteinConsumed' => $user->getTodaysProtein(),
        'carbsConsumed' => $user->getTodaysCarbs(),
        'fatConsumed' => $user->getTodaysFat(),
        'waterIntake' => $user->getTodaysTotalWater()
    ];
    
    // Calculate percentages
    $caloriesTarget = $dailyTarget['calories'] ?? 2000;
    $caloriesPercentage = $caloriesTarget > 0 ? min(100, round(($todaysStats['caloriesConsumed'] / $caloriesTarget) * 100)) : 0;
    
    $waterTarget = $dailyTarget['water'] ?? 2.5;
    $waterPercentage = $waterTarget > 0 ? min(100, round(($todaysStats['waterIntake'] / $waterTarget) * 100)) : 0;
    
    $proteinTarget = $dailyTarget['protein'] ?? 150;
    $proteinPercentage = $proteinTarget > 0 ? min(100, round(($todaysStats['proteinConsumed'] / $proteinTarget) * 100)) : 0;
    
    $carbsTarget = $dailyTarget['carbs'] ?? 200;
    $carbsPercentage = $carbsTarget > 0 ? min(100, round(($todaysStats['carbsConsumed'] / $carbsTarget) * 100)) : 0;
    
    $fatTarget = $dailyTarget['fat'] ?? 50;
    $fatPercentage = $fatTarget > 0 ? min(100, round(($todaysStats['fatConsumed'] / $fatTarget) * 100)) : 0;
    
    // Get today's meals
    $todaysMeals = [];
    if ($assignedPlan) {
        $todaysMealsArray = $assignedPlan->getTodaysMeals();
        
        // Get consumed meals
        $consumedMeals = [];
        foreach ($user->getMealConsumptions() as $consumption) {
            $today = new \DateTime('today');
            $tomorrow = new \DateTime('tomorrow');
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $consumedMeals[$consumption->getMeal()->getId()] = true;
            }
        }
        
        // Organize meals by meal time
        foreach ($todaysMealsArray as $meal) {
            $mealTime = $meal->getMealTime();
            if (!isset($todaysMeals[$mealTime])) {
                $todaysMeals[$mealTime] = [];
            }
            
            $todaysMeals[$mealTime][] = [
                'id' => $meal->getId(),
                'name' => $meal->getName(),
                'description' => $meal->getDescription(),
                'calories' => $meal->getCalories(),
                'protein' => $meal->getProtein(),
                'carbs' => $meal->getCarbs(),
                'fat' => $meal->getFat(),
                'consumed' => isset($consumedMeals[$meal->getId()]),
                'type' => 'plan'
            ];
        }
    }
    
    // Get water logs
    $waterLogs = [];
    foreach ($user->getTodaysWaterIntakes() as $intake) {
        $waterLogs[] = [
            'amount' => $intake->getAmount(),
            'time' => $intake->getConsumedAt()
        ];
    }
    
    // Get progress data
    $latestProgress = $user->getLatestProgress();
    $onboardingProgress = $user->getOnboardingProgress();
    
    $startWeight = $onboardingProgress?->getWeight();
    $currentWeight = $latestProgress?->getWeight();
    $targetWeight = $latestProgress?->getTargetWeight();
    $userGoal = $latestProgress?->getPrimaryGoal() ?? 'Weight Loss';
    
    // Calculate goal progress
    $goalProgress = 0;
    if ($startWeight && $currentWeight && $targetWeight) {
        if ($targetWeight > $startWeight) {
            $totalToGain = $targetWeight - $startWeight;
            $gained = $currentWeight - $startWeight;
            $goalProgress = $totalToGain > 0 ? min(100, round(($gained / $totalToGain) * 100, 1)) : 0;
        } else {
            $totalToLose = $startWeight - $targetWeight;
            $lost = $startWeight - $currentWeight;
            $goalProgress = $totalToLose > 0 ? min(100, round(($lost / $totalToLose) * 100, 1)) : 0;
        }
    }
    
    // Prepare data for PDF
    $data = [
        'user' => $user,
        'assignedPlan' => $assignedPlan,
        'dailyTarget' => $dailyTarget,
        'todaysStats' => $todaysStats,
        'todaysMeals' => $todaysMeals,
        'waterLogs' => $waterLogs,
        'startWeight' => $startWeight,
        'currentWeight' => $currentWeight,
        'targetWeight' => $targetWeight,
        'userGoal' => $userGoal,
        'goalProgress' => $goalProgress,
        'caloriesPercentage' => $caloriesPercentage,
        'waterPercentage' => $waterPercentage,
        'proteinPercentage' => $proteinPercentage,
        'carbsPercentage' => $carbsPercentage,
        'fatPercentage' => $fatPercentage,
        'progressEntries' => $user->getProgressEntries()
    ];
    
    // Generate PDF
    $pdfContent = $pdfService->generateNutritionReport($data);
    
    // Create response with PDF
    $response = new Response($pdfContent);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'attachment; filename="nutrition_report_' . date('Y-m-d') . '.pdf"');
    
    return $response;
}

}