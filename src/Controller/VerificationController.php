<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Get the user from session (stored during registration)
        $userEmail = $request->getSession()->get('pending_verification_email');
        
        if (!$userEmail) {
            $this->addFlash('error', 'No pending verification found. Please register first.');
            return $this->redirectToRoute('app_register');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_register');
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $verificationCode = $request->request->get('verification_code', '');

            try {
                // Check if code is expired
                if ($user->isVerificationCodeExpired()) {
                    throw new \RuntimeException('Verification code has expired. Please register again.');
                }

                // Check if code is correct
                if ($verificationCode !== $user->getVerificationCode()) {
                    throw new \RuntimeException('Invalid verification code. Please try again.');
                }

                // Mark user as verified
                $user->setVerified(true);
                $user->setVerificationCode(null);
                $user->setVerificationCodeExpiresAt(null);

                $entityManager->flush();

                // Clear session
                $request->getSession()->remove('pending_verification_email');

                $this->addFlash('success', 'Email verified successfully! You can now login.');
                return $this->redirectToRoute('app_login');

            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('verification/verify_email.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userEmail = $request->getSession()->get('pending_verification_email');
        
        if (!$userEmail) {
            return $this->json(['error' => 'No pending verification found'], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        try {
            // Resend email with new code
            $service = $this->container->get('App\Service\EmailVerificationService');
            $service->sendVerificationEmail($user);
            $entityManager->flush();

            $this->addFlash('success', 'Verification code sent to your email.');
            return $this->redirectToRoute('app_verify_email');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Could not resend verification code: ' . $e->getMessage());
            return $this->redirectToRoute('app_verify_email');
        }
    }
}
