<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();
        $stats = $this->getStatistics($userRepository);
        $user = $this->getUser();
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/edit_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete')]
    public function deleteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $entityManager->remove($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'User deleted successfully!');
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/user/add', name: 'app_admin_user_add', methods: ['POST'])]
    public function addUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $name = $request->request->get('name', '');
            $email = $request->request->get('email', '');
            $roleType = $request->request->get('role', 'athlete');

            // Validate inputs
            if (empty($name) || empty($email)) {
                return new JsonResponse(['success' => false, 'message' => 'Name and email are required.'], 400);
            }

            if (strlen($name) < 2 || strlen($name) > 100) {
                return new JsonResponse(['success' => false, 'message' => 'Name must be between 2 and 100 characters.'], 400);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
            }

            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                return new JsonResponse(['success' => false, 'message' => 'This email is already in use.'], 400);
            }

            // Validate role
            if (!in_array($roleType, ['athlete', 'coach', 'admin'])) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid role selected.'], 400);
            }

            // Create new user
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setRoleType($roleType);
            
            // Generate temporary password
            $tempPassword = bin2hex(random_bytes(8)); // 16 char password
            $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
            $user->setPassword($hashedPassword);
            
            // Set default role
            $roleMap = [
                'athlete' => 'ROLE_ATHLETE',
                'coach' => 'ROLE_COACH',
                'admin' => 'ROLE_ADMIN',
            ];
            $user->setRoles([$roleMap[$roleType]]);
            
            // Set created date
            $user->setCreatedAt(new \DateTime());
            $user->setVerified(true); // Admin created users are auto-verified

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'User added successfully! A temporary password has been generated.',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'roleType' => $user->getRoleType(),
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/admin/user/{id}/details', name: 'app_admin_user_details')]
    public function getUserDetails(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return new JsonResponse([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roleType' => $user->getRoleType(),
            'createdAt' => $user->getCreatedAt()?->format('F d, Y'),
            'isVerified' => $user->isVerified(),
            'roleLabel' => ucfirst($user->getRoleType()),
            'statusBadge' => $user->isVerified() ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Pending</span>',
        ]);
    }

    private function getStatistics(UserRepository $userRepository): array
    {
        $users = $userRepository->findAll();
        $totalUsers = count($users);
        
        $roleCounts = [
            'athlete' => 0,
            'coach' => 0,
            'admin' => 0,
        ];
        
        foreach ($users as $user) {
            $roleType = $user->getRoleType();
            if (isset($roleCounts[$roleType])) {
                $roleCounts[$roleType]++;
            }
        }
        
        // Get new users this month
        $newUsersThisMonth = 0;
        $currentMonth = date('n');
        
        foreach ($users as $user) {
            $createdAt = $user->getId(); // We'll modify User entity to have createdAt
            // For now, we'll use a simple count
        }
        
        return [
            'totalUsers' => $totalUsers,
            'roleCounts' => $roleCounts,
            'athletes' => $roleCounts['athlete'],
            'coaches' => $roleCounts['coach'],
            'admins' => $roleCounts['admin'],
            'newUsersThisMonth' => $newUsersThisMonth,
        ];
    }
}