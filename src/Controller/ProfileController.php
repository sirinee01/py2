<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $name = $request->request->get('name', '');
                $email = $request->request->get('email', '');

                // Validate name
                if (empty($name)) {
                    throw new \RuntimeException('Name is required.');
                }

                if (strlen($name) < 2 || strlen($name) > 100) {
                    throw new \RuntimeException('Name must be between 2 and 100 characters.');
                }

                // Validate email
                if (empty($email)) {
                    throw new \RuntimeException('Email is required.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Please enter a valid email address.');
                }

                // Check if email is already used by another user
                if ($email !== $user->getEmail()) {
                    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($existingUser) {
                        throw new \RuntimeException('This email is already in use.');
                    }
                }

                $user->setName($name);
                $user->setEmail($email);
                $entityManager->flush();

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('app_dashboard');

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating your profile.');
                error_log('Profile update error: ' . $e->getMessage());
            }
        }

        return $this->render('profile/edit_profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password')]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $currentPassword = $request->request->get('current_password', '');
                $newPassword = $request->request->get('new_password', '');
                $confirmPassword = $request->request->get('confirm_password', '');

                // Validate current password
                if (empty($currentPassword)) {
                    throw new \RuntimeException('Current password is required.');
                }

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    throw new \RuntimeException('Current password is incorrect.');
                }

                // Validate new password
                if (empty($newPassword) || empty($confirmPassword)) {
                    throw new \RuntimeException('New password fields are required.');
                }

                if ($newPassword !== $confirmPassword) {
                    throw new \RuntimeException('Passwords do not match.');
                }

                if (strlen($newPassword) < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters long.');
                }

                if ($currentPassword === $newPassword) {
                    throw new \RuntimeException('New password must be different from the current password.');
                }

                // Update password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();

                $this->addFlash('success', 'Password changed successfully!');
                return $this->redirectToRoute('app_dashboard');

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while changing your password.');
                error_log('Password change error: ' . $e->getMessage());
            }
        }

        return $this->render('profile/change_password.html.twig');
    }
}
