<?php

namespace App\Controller;

use App\Repository\NutritionPlanRepository;
use App\Repository\MealRepository;
use App\Repository\CompetitionRepository;
use App\Repository\CompetitionApplicationRepository;
use App\Entity\CompetitionApplication;
use Symfony\Component\HttpClient\HttpClient;  // ← ADD THIS LINE
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AthleteController extends AbstractController
{
    #[Route('/athlete/dashboard', name: 'app_athlete_dashboard')]
    public function dashboard(NutritionPlanRepository $nutritionPlanRepo, MealRepository $mealRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        
        // Get assigned nutrition plan - you'll need to implement this
        $assignedPlan = null; // Temporary - implement based on your system
        
        // Get today's meals from assigned plan
        $todaysMeals = $assignedPlan ? [] : []; // Temporary
        
        return $this->render('athlete/dashboard.html.twig', [
            'user' => $user,
            'assignedPlan' => $assignedPlan,
            'todaysMeals' => $todaysMeals,
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

    // EXERCISE FUNCTIONALITY FOR ATHLETES

    #[Route('/athlete/exercises', name: 'app_athlete_exercises')]
    public function exercises(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $bodyParts = [
            'chest', 'back', 'upper arms', 'lower arms', 
            'upper legs', 'lower legs', 'shoulders', 'waist', 'cardio'
        ];
        
        return $this->render('athlete/exercises.html.twig', [
            'bodyParts' => $bodyParts,
            'exercises' => [],
            'bodyPart' => '',
            'metadata' => [
                'currentPage' => 1,
                'nextPage' => false,
                'previousPage' => false
            ]
        ]);
    }

    #[Route('/athlete/exercises/{bodyPart}', name: 'app_athlete_exercises_by_bodypart')]
    public function exercisesByBodyPart(string $bodyPart, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        
        // Direct API call
        $exercises = $this->fetchExercisesFromApi($bodyPart, $page, $limit);
        
        $bodyParts = [
            'chest', 'back', 'upper arms', 'lower arms', 
            'upper legs', 'lower legs', 'shoulders', 'waist', 'cardio'
        ];
        
        return $this->render('athlete/exercises.html.twig', [
            'bodyPart' => $bodyPart,
            'bodyParts' => $bodyParts,
            'exercises' => $exercises['data'] ?? [],
            'metadata' => $exercises['metadata'] ?? [
                'currentPage' => $page,
                'nextPage' => false,
                'previousPage' => false,
                'totalExercises' => 0
            ]
        ]);
    }
    
    private function fetchExercisesFromApi(string $bodyPart, int $page = 1, int $limit = 12): array
    {
        try {
            $client = HttpClient::create();
            $offset = ($page - 1) * $limit;
            
            $response = $client->request('GET', 
                'https://exercisedb.p.rapidapi.com/exercises/bodyPart/' . $bodyPart, 
                [
                    'headers' => [
                        'X-RapidAPI-Key' => $_ENV['EXERCISE_API_KEY'] ?? 'your-key-here',
                        'X-RapidAPI-Host' => 'exercisedb.p.rapidapi.com'
                    ],
                    'query' => [
                        'limit' => $limit,
                        'offset' => $offset
                    ]
                ]
            );
            
            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                
                // Transform to match template
                $transformedData = array_map(function($exercise) {
                    return [
                        'name' => $exercise['name'] ?? 'Unknown Exercise',
                        'targetMuscles' => isset($exercise['target']) ? [$exercise['target']] : ['Unknown'],
                        'equipments' => isset($exercise['equipment']) ? [$exercise['equipment']] : ['None'],
                        'gifUrl' => $exercise['gifUrl'] ?? null,
                        'instructions' => $exercise['instructions'] ?? ['No instructions available'],
                        'bodyPart' => $exercise['bodyPart'] ?? 'Unknown'
                    ];
                }, $data);
                
                // Get total count for pagination (simplified - would need separate API call in real scenario)
                $totalExercises = count($data) + $offset; // Approximation
                $hasMore = count($data) >= $limit;
                
                return [
                    'data' => $transformedData,
                    'metadata' => [
                        'currentPage' => $page,
                        'nextPage' => $hasMore,
                        'previousPage' => $page > 1,
                        'totalExercises' => $totalExercises,
                        'limit' => $limit
                    ]
                ];
            }
        } catch (\Exception $e) {
            // Log error
            error_log('Exercise API Error: ' . $e->getMessage());
        }
        
        return [
            'data' => [],
            'metadata' => [
                'currentPage' => $page,
                'nextPage' => false,
                'previousPage' => false,
                'totalExercises' => 0,
                'limit' => $limit
            ]
        ];
    }

    // COMPETITION METHODS FOR ATHLETES

    #[Route('/athlete/competitions', name: 'app_athlete_competitions')]
    public function competitions(CompetitionRepository $competitionRepo, CompetitionApplicationRepository $applicationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        
        // Get all competitions
        $competitions = $competitionRepo->findAll();
        
        // Get user's applications
        $userApplications = $applicationRepo->findBy(['athlete' => $user]);
        
        // Create a map of competition ID to application status
        $applicationMap = [];
        foreach ($userApplications as $application) {
            $applicationMap[$application->getCompetition()->getId()] = $application->getStatus();
        }
        
        return $this->render('athlete/competitions.html.twig', [
            'competitions' => $competitions,
            'applicationMap' => $applicationMap,
            'userApplications' => $userApplications,
        ]);
    }

    #[Route('/athlete/competition/{id}/view', name: 'app_athlete_competition_view')]
    public function viewCompetition(int $id, CompetitionRepository $competitionRepo, CompetitionApplicationRepository $applicationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        $competition = $competitionRepo->find($id);
        
        if (!$competition) {
            throw $this->createNotFoundException('Competition not found');
        }
        
        // Check if user has already applied
        $existingApplication = $applicationRepo->findOneBy([
            'athlete' => $user,
            'competition' => $competition
        ]);
        
        return $this->render('athlete/competition_view.html.twig', [
            'competition' => $competition,
            'existingApplication' => $existingApplication,
        ]);
    }

    #[Route('/athlete/competition/{id}/apply', name: 'app_athlete_competition_apply')]
    public function applyForCompetition(int $id, CompetitionRepository $competitionRepo, CompetitionApplicationRepository $applicationRepo, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        $competition = $competitionRepo->find($id);
        
        if (!$competition) {
            throw $this->createNotFoundException('Competition not found');
        }
        
        // Check if competition is full
        if ($competition->isFull()) {
            $this->addFlash('error', 'This competition is already full.');
            return $this->redirectToRoute('app_athlete_competitions');
        }
        
        // Check if user has already applied
        $existingApplication = $applicationRepo->findOneBy([
            'athlete' => $user,
            'competition' => $competition
        ]);
        
        if ($existingApplication) {
            $this->addFlash('warning', 'You have already applied for this competition.');
            return $this->redirectToRoute('app_athlete_competition_view', ['id' => $id]);
        }
        
        // Create new application
        $application = new CompetitionApplication();
        $application->setAthlete($user);
        $application->setCompetition($competition);
        $application->setStatus('pending');
        
        // Get notes from form if submitted
        if ($request->isMethod('POST')) {
            $notes = $request->request->get('notes');
            $application->setNotes($notes);
            
            $entityManager->persist($application);
            $entityManager->flush();
            
            $this->addFlash('success', 'Successfully applied for the competition!');
            return $this->redirectToRoute('app_athlete_competition_view', ['id' => $id]);
        }
        
        return $this->redirectToRoute('app_athlete_competition_view', ['id' => $id]);
    }

    #[Route('/athlete/application/{id}/withdraw', name: 'app_athlete_application_withdraw')]
    public function withdrawApplication(int $id, CompetitionApplicationRepository $applicationRepo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        $application = $applicationRepo->find($id);
        
        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }
        
        // Check if the application belongs to the user
        if ($application->getAthlete() !== $user) {
            throw $this->createAccessDeniedException('You cannot withdraw this application');
        }
        
        // Only allow withdrawal if status is pending
        if ($application->getStatus() !== 'pending') {
            $this->addFlash('error', 'You can only withdraw pending applications.');
            return $this->redirectToRoute('app_athlete_competitions');
        }
        
        $entityManager->remove($application);
        $entityManager->flush();
        
        $this->addFlash('success', 'Application withdrawn successfully.');
        return $this->redirectToRoute('app_athlete_competitions');
    }

    #[Route('/athlete/my-applications', name: 'app_athlete_my_applications')]
    public function myApplications(CompetitionApplicationRepository $applicationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $user = $this->getUser();
        $applications = $applicationRepo->findBy(['athlete' => $user], ['appliedAt' => 'DESC']);
        
        return $this->render('athlete/my_applications.html.twig', [
            'applications' => $applications,
        ]);
    }
}