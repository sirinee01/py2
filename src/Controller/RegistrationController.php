<?php
<<<<<<< HEAD
=======
// src/Controller/RegistrationController.php
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
<<<<<<< HEAD
use App\Service\EmailVerificationService;
=======
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
<<<<<<< HEAD
use Symfony\Component\Validator\Validator\ValidatorInterface;
=======
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
<<<<<<< HEAD
        ValidatorInterface $validator,
        EmailVerificationService $emailVerificationService
    ): Response
    {
=======
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $eventDispatcher
    ): Response
    {
        // If user is already logged in, redirect them
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

<<<<<<< HEAD
        if ($form->isSubmitted()) {
            try {
                // Validate form data
                if (!$form->isValid()) {
                    // Get all form errors
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }
                    
                    if (!empty($errors)) {
                        throw new \RuntimeException('Form validation failed: ' . implode(', ', $errors));
                    }
                }

=======
        if ($form->isSubmitted() && $form->isValid()) {
            try {
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
                // Check if email already exists
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    throw new \RuntimeException('Email already registered. Please use a different email or login.');
                }

<<<<<<< HEAD
                // Validate entity
                $validationErrors = $validator->validate($user);
                if (count($validationErrors) > 0) {
                    $errorMessages = [];
                    foreach ($validationErrors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    throw new \RuntimeException('Validation failed: ' . implode(', ', $errorMessages));
                }

                // encode the plain password
                $plainPassword = $form->get('plainPassword')->getData();
                if (empty($plainPassword)) {
                    throw new \RuntimeException('Password cannot be empty.');
                }
=======
                // Encode the plain password
                $plainPassword = $form->get('plainPassword')->getData();
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
                
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                // Set the roleType and roles
                $roleType = $form->get('roleType')->getData();
<<<<<<< HEAD
                if (empty($roleType)) {
                    throw new \RuntimeException('Please select a role.');
                }
                
=======
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
                $user->setRoleType($roleType);
                $user->setRoles(['ROLE_USER', 'ROLE_' . strtoupper($roleType)]);

                // Set creation date
                $user->setCreatedAt(new \DateTime());
<<<<<<< HEAD
                
                // Mark user as unverified (awaiting email verification)
                $user->setVerified(false);

                // Save user temporarily without email verification
                $entityManager->persist($user);
                $entityManager->flush();

                // Send verification email
                try {
                    $emailVerificationService->sendVerificationEmail($user);
                    $entityManager->flush();
                } catch (\Exception $e) {
                    // Log error but don't fail registration
                    error_log('Failed to send verification email: ' . $e->getMessage());
                    $this->addFlash('warning', 'Registration successful but we could not send the verification email. Please contact support.');
                    return $this->redirectToRoute('app_login');
                }

                // Store email in session for verification page
                $request->getSession()->set('pending_verification_email', $user->getEmail());

                $this->addFlash('success', 'Registration successful! Please check your email for the verification code.');

                return $this->redirectToRoute('app_verify_email');

            } catch (\RuntimeException $e) {
                // Handle runtime exceptions (validation errors, duplicate email, etc.)
                $this->addFlash('error', $e->getMessage());
                
            } catch (\Exception $e) {
                // Handle all other exceptions (database errors, etc.)
                $this->addFlash('error', 'An unexpected error occurred. Please try again.');
                
                // Log the full error for debugging (remove in production)
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('error', 'Error details: ' . $e->getMessage());
                }
                
                // Log error
                error_log('Registration Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
=======

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
>>>>>>> 6857de554cfd071bc09489d64f6ff7fcfbf24b63
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}