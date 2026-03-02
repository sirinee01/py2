<?php
// src/Controller/ProgressController.php

namespace App\Controller;

use App\Entity\Progress;
use App\Form\ProgressUpdateType;
use App\Repository\ProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/progress')]
class ProgressController extends AbstractController
{
    #[Route('/', name: 'app_progress_index')]
    public function index(ProgressRepository $progressRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        $progressEntries = $progressRepository->findByUser($user);
        $latestProgress = $user->getLatestProgress();
        
        // Prepare data for charts
        $weightHistory = $progressRepository->getWeightHistory($user);
        
        $chartData = [
            'labels' => [],
            'weights' => [],
            'dates' => []
        ];
        
        foreach ($weightHistory as $entry) {
            $chartData['labels'][] = $entry->getRecordedAt()->format('M d');
            $chartData['weights'][] = $entry->getWeight();
            $chartData['dates'][] = $entry->getRecordedAt()->format('Y-m-d');
        }
        
        return $this->render('progress/index.html.twig', [
            'progressEntries' => $progressEntries,
            'latestProgress' => $latestProgress,
            'chartData' => $chartData,
        ]);
    }

    #[Route('/new', name: 'app_progress_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        $progress = new Progress();
        $progress->setUser($user);
        $progress->setIsInitialOnboarding(false);
        
        // Pre-fill with latest data if available
        $latest = $user->getLatestProgress();
        if ($latest) {
            $progress->setHeight($latest->getHeight());
            // Don't copy weight - user should enter current weight
        }
        
        $form = $this->createForm(ProgressUpdateType::class, $progress);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($progress);
            $entityManager->flush();
            
            $this->addFlash('success', 'Progress updated successfully!');
            
            return $this->redirectToRoute('app_progress_index');
        }
        
        return $this->render('progress/new.html.twig', [
            'form' => $form->createView(),
            'latestProgress' => $latest,
        ]);
    }

    #[Route('/{id}', name: 'app_progress_show')]
    public function show(Progress $progress): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Ensure user can only view their own progress
        if ($progress->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        
        return $this->render('progress/show.html.twig', [
            'progress' => $progress,
        ]);
    }
}