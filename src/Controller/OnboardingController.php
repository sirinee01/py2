<?php
// src/Controller/OnboardingController.php

namespace App\Controller;

use App\Entity\Progress;
use App\Entity\User;
use App\Form\OnboardingStep1Type;
use App\Form\OnboardingStep2Type;
use App\Form\OnboardingStep3Type;
use App\Form\OnboardingStep4Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OnboardingController extends AbstractController
{
    private const ONBOARDING_STEPS = 4;
    
    #[Route('/onboarding', name: 'app_onboarding_start')]
    public function start(): Response
    {
        // If user already completed onboarding, redirect to dashboard
        if ($this->getUser() && $this->getUser()->isOnboardingCompleted()) {
            return $this->redirectToDashboard();
        }
        
        return $this->redirectToRoute('app_onboarding_step1');
    }
    
    #[Route('/onboarding/step1', name: 'app_onboarding_step1')]
    public function step1(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        // Create a progress object to hold step1 data
        $progress = new Progress();
        $progress->setUser($user);
        
        $form = $this->createForm(OnboardingStep1Type::class, $progress);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Store step1 data in session
            $session->set('onboarding_step1', [
                'age' => $progress->getAge(),
                'gender' => $form->get('gender')->getData(),
                'height' => $progress->getHeight(),
                'weight' => $progress->getWeight(),
                'activityLevel' => $progress->getActivityLevel(),
                'workoutsPerWeek' => $progress->getWorkoutsPerWeek(),
            ]);
            
            // Update user with basic info
            if ($form->get('gender')->getData()) {
                $user->setGender($form->get('gender')->getData());
            }
            
            if ($progress->getAge()) {
                // Approximate birthdate from age (rough estimate)
                $birthDate = new \DateTime();
                $birthDate->modify('-' . $progress->getAge() . ' years');
                $user->setBirthDate($birthDate);
            }
            
            $entityManager->flush();
            
            return $this->redirectToRoute('app_onboarding_step2');
        }
        
        return $this->render('onboarding/step1.html.twig', [
            'form' => $form->createView(),
            'step' => 1,
            'totalSteps' => self::ONBOARDING_STEPS,
            'progress' => 25,
        ]);
    }
    
    #[Route('/onboarding/step2', name: 'app_onboarding_step2')]
    public function step2(Request $request, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if step1 is completed
        if (!$session->has('onboarding_step1')) {
            return $this->redirectToRoute('app_onboarding_step1');
        }
        
        $progress = new Progress();
        $step1Data = $session->get('onboarding_step1');
        
        // Pre-fill with step1 data for calculations
        $progress->setAge($step1Data['age']);
        $progress->setHeight($step1Data['height']);
        $progress->setWeight($step1Data['weight']);
        $progress->setActivityLevel($step1Data['activityLevel']);
        
        $form = $this->createForm(OnboardingStep2Type::class, $progress);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Store step2 data in session
            $session->set('onboarding_step2', [
                'primaryGoal' => $progress->getPrimaryGoal(),
                'targetWeight' => $progress->getTargetWeight(),
                'goalTimeline' => $progress->getGoalTimeline(),
                'dailyCalorieIntake' => $progress->getDailyCalorieIntake(),
                'dailyWaterIntake' => $progress->getDailyWaterIntake(),
            ]);
            
            return $this->redirectToRoute('app_onboarding_step3');
        }
        
        // Calculate recommendations based on step1 data
        $recommendations = [
            'bmr' => $progress->getBmr(),
            'tdee' => $progress->getTdee(),
            'bmi' => $progress->getBmi(),
            'bmiCategory' => $progress->getBmiCategory(),
        ];
        
        return $this->render('onboarding/step2.html.twig', [
            'form' => $form->createView(),
            'step' => 2,
            'totalSteps' => self::ONBOARDING_STEPS,
            'progress' => 50,
            'recommendations' => $recommendations,
            'step1Data' => $step1Data,
        ]);
    }
    
    #[Route('/onboarding/step3', name: 'app_onboarding_step3')]
    public function step3(Request $request, SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if previous steps are completed
        if (!$session->has('onboarding_step1') || !$session->has('onboarding_step2')) {
            return $this->redirectToRoute('app_onboarding_step1');
        }
        
        $progress = new Progress();
        $form = $this->createForm(OnboardingStep3Type::class, $progress);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Store step3 data in session
            $session->set('onboarding_step3', [
                'proteinIntake' => $progress->getProteinIntake(),
                'carbIntake' => $progress->getCarbIntake(),
                'fatIntake' => $progress->getFatIntake(),
                'dietaryRestrictions' => $progress->getDietaryRestrictions(),
            ]);
            
            return $this->redirectToRoute('app_onboarding_step4');
        }
        
        return $this->render('onboarding/step3.html.twig', [
            'form' => $form->createView(),
            'step' => 3,
            'totalSteps' => self::ONBOARDING_STEPS,
            'progress' => 75,
        ]);
    }
    
    #[Route('/onboarding/step4', name: 'app_onboarding_step4')]
    public function step4(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if all previous steps are completed
        if (!$session->has('onboarding_step1') || !$session->has('onboarding_step2') || !$session->has('onboarding_step3')) {
            return $this->redirectToRoute('app_onboarding_step1');
        }
        
        $user = $this->getUser();
        $progress = new Progress();
        $form = $this->createForm(OnboardingStep4Type::class, $progress);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Get all data from session
            $step1Data = $session->get('onboarding_step1');
            $step2Data = $session->get('onboarding_step2');
            $step3Data = $session->get('onboarding_step3');
            
            // Create the final progress entry
            $finalProgress = new Progress();
            $finalProgress->setUser($user);
            $finalProgress->setIsInitialOnboarding(true);
            $finalProgress->setRecordedAt(new \DateTime());
            
            // Step 1 data
            $finalProgress->setAge($step1Data['age']);
            $finalProgress->setHeight($step1Data['height']);
            $finalProgress->setWeight($step1Data['weight']);
            $finalProgress->setActivityLevel($step1Data['activityLevel']);
            $finalProgress->setWorkoutsPerWeek($step1Data['workoutsPerWeek']);
            
            // Step 2 data
            $finalProgress->setPrimaryGoal($step2Data['primaryGoal']);
            $finalProgress->setTargetWeight($step2Data['targetWeight']);
            $finalProgress->setGoalTimeline($step2Data['goalTimeline']);
            $finalProgress->setDailyCalorieIntake($step2Data['dailyCalorieIntake']);
            $finalProgress->setDailyWaterIntake($step2Data['dailyWaterIntake']);
            
            // Step 3 data
            $finalProgress->setProteinIntake($step3Data['proteinIntake']);
            $finalProgress->setCarbIntake($step3Data['carbIntake']);
            $finalProgress->setFatIntake($step3Data['fatIntake']);
            $finalProgress->setDietaryRestrictions($step3Data['dietaryRestrictions']);
            
            // Step 4 data (health conditions, measurements)
            $finalProgress->setHealthConditions($progress->getHealthConditions());
            $finalProgress->setBodyFatPercentage($progress->getBodyFatPercentage());
            $finalProgress->setMuscleMass($progress->getMuscleMass());
            
            // Additional measurements
            $measurements = [
                'chest' => $form->get('chestMeasurement')->getData(),
                'waist' => $form->get('waistMeasurement')->getData(),
                'hips' => $form->get('hipsMeasurement')->getData(),
                'arms' => $form->get('armsMeasurement')->getData(),
                'thighs' => $form->get('thighsMeasurement')->getData(),
            ];
            $finalProgress->setMeasurements(array_filter($measurements));
            
            // Save to database
            $entityManager->persist($finalProgress);
            
            // Mark user onboarding as completed
            $user->setOnboardingCompleted(true);
            
            // Update user's assigned nutrition plan based on goals
            $this->assignInitialNutritionPlan($user, $finalProgress, $entityManager);
            
            $entityManager->flush();
            
            // Clear session data
            $session->remove('onboarding_step1');
            $session->remove('onboarding_step2');
            $session->remove('onboarding_step3');
            
            $this->addFlash('success', 'Welcome aboard! Your profile has been set up successfully.');
            
            return $this->redirectToDashboard();
        }
        
        return $this->render('onboarding/step4.html.twig', [
            'form' => $form->createView(),
            'step' => 4,
            'totalSteps' => self::ONBOARDING_STEPS,
            'progress' => 100,
            'user' => $user,
        ]);
    }
    
    /**
     * Assign an initial nutrition plan based on user's goals
     */
    private function assignInitialNutritionPlan(User $user, Progress $progress, EntityManagerInterface $entityManager): void
    {
        // This would query for appropriate nutrition plans based on goals
        // For now, we'll just log that this would happen
        // You would implement logic to find and assign a plan
    }
    
    /**
     * Redirect user to their appropriate dashboard
     */
    private function redirectToDashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        return match($user->getRoleType()) {
            'admin' => $this->redirectToRoute('app_admin'),
            'coach' => $this->redirectToRoute('app_coach_dashboard'),
            default => $this->redirectToRoute('app_athlete_dashboard'),
        };
    }
}