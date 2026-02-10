<?php

namespace App\Controller;

use App\Entity\Competition;
use App\Entity\CompetitionApplication;
use App\Form\CompetitionType;
use App\Repository\CompetitionRepository;
use App\Repository\CompetitionApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminCompetitionController extends AbstractController
{
    #[Route('/admin/competitions', name: 'app_admin_competitions')]
    public function index(CompetitionRepository $competitionRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $competitions = $competitionRepo->findAll();
        
        return $this->render('admin/competition/index.html.twig', [
            'competitions' => $competitions,
        ]);
    }

    #[Route('/admin/competition/new', name: 'app_admin_competition_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $competition = new Competition();
        $competition->setOrganizer($this->getUser());
        
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('competitions_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
                
                $competition->setImage($newFilename);
            }
            
            $entityManager->persist($competition);
            $entityManager->flush();

            $this->addFlash('success', 'Competition created successfully!');
            return $this->redirectToRoute('app_admin_competitions');
        }

        return $this->render('admin/competition/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/competition/{id}/applications', name: 'app_admin_competition_applications')]
    public function applications(Competition $competition, CompetitionApplicationRepository $applicationRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $applications = $applicationRepo->findBy(['competition' => $competition]);
        
        return $this->render('admin/competition/applications.html.twig', [
            'competition' => $competition,
            'applications' => $applications,
        ]);
    }

    #[Route('/admin/application/{id}/approve', name: 'app_admin_application_approve')]
    public function approveApplication(CompetitionApplication $application, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $application->setStatus('approved');
        $application->setApprovedAt(new \DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Application approved successfully!');
        return $this->redirectToRoute('app_admin_competition_applications', ['id' => $application->getCompetition()->getId()]);
    }

    #[Route('/admin/application/{id}/reject', name: 'app_admin_application_reject')]
    public function rejectApplication(CompetitionApplication $application, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $application->setStatus('rejected');
        $application->setRejectedAt(new \DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Application rejected successfully!');
        return $this->redirectToRoute('app_admin_competition_applications', ['id' => $application->getCompetition()->getId()]);
    }

    #[Route('/admin/competition/{id}/edit', name: 'app_admin_competition_edit')]
    public function edit(Request $request, Competition $competition, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(CompetitionType::class, $competition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('competitions_directory'),
                        $newFilename
                    );
                    // Remove old image if exists
                    if ($competition->getImage()) {
                        $oldImage = $this->getParameter('competitions_directory').'/'.$competition->getImage();
                        if (file_exists($oldImage)) {
                            unlink($oldImage);
                        }
                    }
                    $competition->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image.');
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Competition updated successfully!');
            return $this->redirectToRoute('app_admin_competitions');
        }

        return $this->render('admin/competition/edit.html.twig', [
            'form' => $form->createView(),
            'competition' => $competition,
        ]);
    }

    #[Route('/admin/competition/{id}/delete', name: 'app_admin_competition_delete')]
    public function delete(Request $request, Competition $competition, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete'.$competition->getId(), $request->request->get('_token'))) {
            // Remove image file if exists
            if ($competition->getImage()) {
                $imagePath = $this->getParameter('competitions_directory').'/'.$competition->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($competition);
            $entityManager->flush();
            
            $this->addFlash('success', 'Competition deleted successfully!');
        }

        return $this->redirectToRoute('app_admin_competitions');
    }
}