<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();
        $stats = $this->getStatistics($userRepository);
        
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
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