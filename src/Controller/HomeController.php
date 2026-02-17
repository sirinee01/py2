<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // If user is logged in, redirect to their dashboard
        if ($this->getUser()) {
            $user = $this->getUser();
            
            // Redirect based on role
            if ($user->getRoleType() === 'admin') {
                return $this->redirectToRoute('app_admin');
            } elseif ($user->getRoleType() === 'coach') {
                return $this->redirectToRoute('app_coach_dashboard');
            } else {
                return $this->redirectToRoute('app_athlete_dashboard');
            }
        }
        
        // If not logged in, show the beautiful landing page
        return $this->render('home/index.html.twig');
    }
}