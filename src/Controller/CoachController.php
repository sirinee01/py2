<?php

namespace App\Controller;

use App\Entity\Meal;
use App\Entity\NutritionPlan;
use App\Entity\User;
use App\Form\MealType;
use App\Form\NutritionPlanType;
use App\Repository\MealRepository;
use App\Repository\NutritionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CoachController extends AbstractController
{
    #[Route('/coach/dashboard', name: 'app_coach_dashboard')]
    public function dashboard(NutritionPlanRepository $nutritionPlanRepo, MealRepository $mealRepo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $coach = $this->getUser();
        $nutritionPlans = $nutritionPlanRepo->findBy(['coach' => $coach]);
        $meals = $mealRepo->findBy(['coach' => $coach]);
        
        // Get athletes count
        $athletes = $entityManager->getRepository(User::class)
            ->findBy(['roleType' => 'athlete']);
        
        return $this->render('coach/dashboard.html.twig', [
            'nutritionPlans' => $nutritionPlans,
            'meals' => $meals,
            'athletes' => $athletes,
        ]);
    }

    #[Route('/coach/athletes', name: 'app_coach_athletes')]
    public function athletesList(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Get all athletes
        $athletes = $entityManager->getRepository(User::class)
            ->findBy(['roleType' => 'athlete']);
        
        // Get coach's nutrition plans
        $nutritionPlans = $entityManager->getRepository(NutritionPlan::class)
            ->findBy(['coach' => $this->getUser()]);
        
        return $this->render('coach/athletes_list.html.twig', [
            'athletes' => $athletes,
            'nutritionPlans' => $nutritionPlans,
        ]);
    }

    #[Route('/coach/athlete/{id}/assign-plan', name: 'app_coach_athlete_assign_plan')]
    public function assignPlanToAthlete(Request $request, User $athlete, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Verify this is an athlete
        if ($athlete->getRoleType() !== 'athlete') {
            $this->addFlash('error', 'You can only assign plans to athletes.');
            return $this->redirectToRoute('app_coach_athletes');
        }
        
        // Get coach's nutrition plans
        $coach = $this->getUser();
        $nutritionPlans = $entityManager->getRepository(NutritionPlan::class)
            ->findBy(['coach' => $coach]);
        
        if ($request->isMethod('POST')) {
            $planId = $request->request->get('plan');
            
            if ($planId === 'none') {
                // Remove current plan
                $athlete->setAssignedNutritionPlan(null);
                $this->addFlash('success', 'Nutrition plan removed from athlete successfully!');
            } else {
                $plan = $entityManager->getRepository(NutritionPlan::class)->find($planId);
                
                if ($plan && $plan->getCoach() === $coach) {
                    $athlete->setAssignedNutritionPlan($plan);
                    $this->addFlash('success', 'Nutrition plan assigned to athlete successfully!');
                } else {
                    $this->addFlash('error', 'Invalid nutrition plan selected.');
                    return $this->redirectToRoute('app_coach_athlete_assign_plan', ['id' => $athlete->getId()]);
                }
            }
            
            $entityManager->flush();
            return $this->redirectToRoute('app_coach_athletes');
        }
        
        return $this->render('coach/assign_plan_to_athlete.html.twig', [
            'athlete' => $athlete,
            'nutritionPlans' => $nutritionPlans,
            'currentPlan' => $athlete->getAssignedNutritionPlan(),
        ]);
    }

    #[Route('/coach/nutrition-plan/new', name: 'app_coach_nutrition_plan_new')]
    public function newNutritionPlan(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $nutritionPlan = new NutritionPlan();
        $nutritionPlan->setCoach($this->getUser());
        
        $form = $this->createForm(NutritionPlanType::class, $nutritionPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($nutritionPlan);
            $entityManager->flush();

            $this->addFlash('success', 'Nutrition plan created successfully!');
            return $this->redirectToRoute('app_coach_dashboard');
        }

        return $this->render('coach/nutrition_plan_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/coach/nutrition-plan/{id}/edit', name: 'app_coach_nutrition_plan_edit')]
    public function editNutritionPlan(Request $request, NutritionPlan $nutritionPlan, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Check if the coach owns this nutrition plan
        if ($nutritionPlan->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this nutrition plan.');
        }
        
        $form = $this->createForm(NutritionPlanType::class, $nutritionPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Nutrition plan updated successfully!');
            return $this->redirectToRoute('app_coach_dashboard');
        }

        return $this->render('coach/nutrition_plan_edit.html.twig', [
            'form' => $form->createView(),
            'nutritionPlan' => $nutritionPlan,
        ]);
    }

    #[Route('/coach/meal/new', name: 'app_coach_meal_new')]
    public function newMeal(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        $meal = new Meal();
        $meal->setCoach($this->getUser());
        
        $form = $this->createForm(MealType::class, $meal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('meals_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
                
                $meal->setImage($newFilename);
            }
            
            $entityManager->persist($meal);
            $entityManager->flush();

            $this->addFlash('success', 'Meal created successfully!');
            return $this->redirectToRoute('app_coach_dashboard');
        }

        return $this->render('coach/meal_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/coach/meal/{id}/edit', name: 'app_coach_meal_edit')]
    public function editMeal(Request $request, Meal $meal, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Check if the coach owns this meal
        if ($meal->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this meal.');
        }
        
        $form = $this->createForm(MealType::class, $meal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('meals_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
                
                // Delete old image if exists
                if ($meal->getImage()) {
                    $oldImage = $this->getParameter('meals_directory').'/'.$meal->getImage();
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                
                $meal->setImage($newFilename);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Meal updated successfully!');
            return $this->redirectToRoute('app_coach_dashboard');
        }

        return $this->render('coach/meal_edit.html.twig', [
            'form' => $form->createView(),
            'meal' => $meal,
        ]);
    }

    #[Route('/coach/nutrition-plan/{id}', name: 'app_coach_nutrition_plan_view')]
    public function viewNutritionPlan(NutritionPlan $nutritionPlan): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Check if the coach owns this nutrition plan
        if ($nutritionPlan->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this nutrition plan.');
        }
        
        return $this->render('coach/nutrition_plan_view.html.twig', [
            'nutritionPlan' => $nutritionPlan,
        ]);
    }

    #[Route('/coach/nutrition-plan/{id}/delete', name: 'app_coach_nutrition_plan_delete')]
    public function deleteNutritionPlan(Request $request, NutritionPlan $nutritionPlan, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Check if the coach owns this nutrition plan
        if ($nutritionPlan->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this nutrition plan.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$nutritionPlan->getId(), $request->request->get('_token'))) {
            $entityManager->remove($nutritionPlan);
            $entityManager->flush();
            
            $this->addFlash('success', 'Nutrition plan deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        
        return $this->redirectToRoute('app_coach_dashboard');
    }

    #[Route('/coach/meal/{id}/delete', name: 'app_coach_meal_delete')]
    public function deleteMeal(Request $request, Meal $meal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COACH');
        
        // Check if the coach owns this meal
        if ($meal->getCoach() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this meal.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$meal->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($meal->getImage()) {
                $imagePath = $this->getParameter('meals_directory').'/'.$meal->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($meal);
            $entityManager->flush();
            
            $this->addFlash('success', 'Meal deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        
        return $this->redirectToRoute('app_coach_dashboard');
    }
}