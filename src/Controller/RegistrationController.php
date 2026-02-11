<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

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

                // Check if email already exists
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    throw new \RuntimeException('Email already registered. Please use a different email or login.');
                }

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
                
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                // Set the roleType and roles
                $roleType = $form->get('roleType')->getData();
                if (empty($roleType)) {
                    throw new \RuntimeException('Please select a role.');
                }
                
                $user->setRoleType($roleType);
                $user->setRoles(['ROLE_USER', 'ROLE_' . strtoupper($roleType)]);

                // Set creation date
                $user->setCreatedAt(new \DateTime());

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Registration successful! You can now login.');

                return $this->redirectToRoute('app_login');

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
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}