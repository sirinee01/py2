<?php
// src/Controller/AthleteCompetitionController.php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\CompetitionApplication;
use App\Repository\CompetitionRepository;
use App\Repository\CompetitionApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AthleteCompetitionController extends AbstractController
{
    #[Route('/athlete/competitions', name: 'app_athlete_competitions')]
    public function index(CompetitionRepository $competitionRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $competitions = $competitionRepo->findAll();
        
        return $this->render('athlete/competitions/index.html.twig', [
            'competitions' => $competitions,
        ]);
    }

    #[Route('/athlete/competition/{id}', name: 'app_athlete_competition_view')]
    public function view(Competition $competition): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        return $this->render('athlete/competitions/view.html.twig', [
            'competition' => $competition,
        ]);
    }

    #[Route('/athlete/competition/{id}/apply', name: 'app_athlete_competition_apply')]
    public function apply(Competition $competition, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        // Check if already applied
        $existingApplication = $entityManager->getRepository(CompetitionApplication::class)
            ->findOneBy([
                'competition' => $competition,
                'athlete' => $this->getUser()
            ]);
        
        if ($existingApplication) {
            $this->addFlash('warning', 'You have already applied to this competition.');
            return $this->redirectToRoute('app_athlete_competition_view', ['id' => $competition->getId()]);
        }
        
        $application = new CompetitionApplication();
        $application->setCompetition($competition);
        $application->setAthlete($this->getUser());
        $application->setStatus('pending');
        
        $entityManager->persist($application);
        $entityManager->flush();
        
        $this->addFlash('success', 'Application submitted successfully!');
        return $this->redirectToRoute('app_athlete_my_competitions');
    }

    #[Route('/athlete/my-competitions', name: 'app_athlete_my_competitions')]
    public function myCompetitions(CompetitionApplicationRepository $applicationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ATHLETE');
        
        $applications = $applicationRepo->findBy(['athlete' => $this->getUser()]);
        
        return $this->render('athlete/competitions/my_competitions.html.twig', [
            'applications' => $applications,
        ]);
    }
}