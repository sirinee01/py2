<?php
// src/Controller/RegistrationController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher
    ): Response
    {
        // If user is already logged in, redirect them
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Check if email already exists
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    throw new \RuntimeException('Email already registered. Please use a different email or login.');
                }

                // Encode the plain password
                $plainPassword = $form->get('plainPassword')->getData();
                
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                // Set the roleType and roles
                $roleType = $form->get('roleType')->getData();
                $user->setRoleType($roleType);
                $user->setRoles(['ROLE_USER', 'ROLE_' . strtoupper($roleType)]);

                // Set creation date
                $user->setCreatedAt(new \DateTime());

                // Set default onboarding status
                $user->setOnboardingCompleted(false);

                // Persist the user
                $entityManager->persist($user);
                $entityManager->flush();

                // ===== PROPER AUTO-LOGIN WITH INJECTED SERVICES =====
                // Create token
                $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                $tokenStorage->setToken($token);
                
                // Fire login event
                $event = new InteractiveLoginEvent($request, $token);
                $eventDispatcher->dispatch($event);

                // Success message
                $this->addFlash('success', 'Registration successful! ' . ($roleType === 'athlete' ? 'Please complete your profile to get started.' : 'Welcome!'));

                // Redirect based on role
                if ($roleType === 'athlete') {
                    return $this->redirectToRoute('app_onboarding_start');
                } else {
                    return $this->redirectToRoute('app_dashboard');
                }

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'An unexpected error occurred. Please try again.');
                
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('error', 'Error details: ' . $e->getMessage());
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}