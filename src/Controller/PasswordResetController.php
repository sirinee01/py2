<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        PasswordResetService $passwordResetService
    ): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');

            try {
                // Find user by email
                $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    throw new \RuntimeException('No account found with this email address.');
                }

                // Send password reset email
                try {
                    $passwordResetService->sendPasswordResetEmail($user);
                    $entityManager->flush();
                    
                    // Store email in session for reset page
                    $request->getSession()->set('password_reset_email', $user->getEmail());

                    $this->addFlash('success', 'Password reset code sent to your email. Check your inbox.');
                    return $this->redirectToRoute('app_reset_password');

                } catch (\Exception $e) {
                    error_log('Failed to send password reset email: ' . $e->getMessage());
                    throw new \RuntimeException('Could not send password reset email. Please try again later.');
                }

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('password_reset/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password')]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $userEmail = $request->getSession()->get('password_reset_email');

        if (!$userEmail) {
            $this->addFlash('error', 'No password reset request found. Please use forgot password form.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            try {
                $resetCode = $request->request->get('reset_code', '');
                $newPassword = $request->request->get('new_password', '');
                $confirmPassword = $request->request->get('confirm_password', '');

                // Validate reset code
                if (!$resetCode) {
                    throw new \RuntimeException('Reset code is required.');
                }

                if ($user->isPasswordResetCodeExpired()) {
                    throw new \RuntimeException('Password reset code has expired. Please request a new one.');
                }

                if ($resetCode !== $user->getPasswordResetCode()) {
                    throw new \RuntimeException('Invalid reset code. Please check your email and try again.');
                }

                // Validate passwords
                if (empty($newPassword) || empty($confirmPassword)) {
                    throw new \RuntimeException('Password fields are required.');
                }

                if ($newPassword !== $confirmPassword) {
                    throw new \RuntimeException('Passwords do not match.');
                }

                if (strlen($newPassword) < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters long.');
                }

                // Update password
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $user->setPasswordResetCode(null);
                $user->setPasswordResetCodeExpiresAt(null);

                $entityManager->flush();

                // Clear session
                $request->getSession()->remove('password_reset_email');

                $this->addFlash('success', 'Password reset successfully! You can now login with your new password.');
                return $this->redirectToRoute('app_login');

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('password_reset/reset_password.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/resend-reset-code', name: 'app_resend_reset_code', methods: ['POST'])]
    public function resendResetCode(
        Request $request,
        EntityManagerInterface $entityManager,
        PasswordResetService $passwordResetService
    ): Response
    {
        $userEmail = $request->getSession()->get('password_reset_email');

        if (!$userEmail) {
            return $this->json(['error' => 'No password reset found'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        try {
            $passwordResetService->sendPasswordResetEmail($user);
            $entityManager->flush();

            $this->addFlash('success', 'Reset code resent to your email.');
            return $this->redirectToRoute('app_reset_password');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Could not resend reset code: ' . $e->getMessage());
            return $this->redirectToRoute('app_reset_password');
        }
    }
}
