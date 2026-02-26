<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\TicketReply;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/support')]
#[IsGranted('ROLE_ATHLETE')]
class SupportController extends AbstractController
{
    #[Route('/', name: 'app_support_index')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $user = $this->getUser();
        $tickets = $ticketRepository->findByAthlete($user);

        return $this->render('support/athlete/index.html.twig', [
            'tickets' => $tickets,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_support_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $ticket = new Ticket();

        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $title = $request->request->get('title');
            $content = $request->request->get('content');

            $ticket->setAthlete($this->getUser());
            $ticket->setType($type);
            $ticket->setTitle($title);
            $ticket->setContent($content);
            $ticket->setStatus('open');

            // Validate the ticket
            $errors = $validator->validate($ticket);

            if (count($errors) === 0) {
                $entityManager->persist($ticket);
                $entityManager->flush();

                $this->addFlash('success', 'Your ' . $type . ' has been submitted successfully!');
                return $this->redirectToRoute('app_support_show', ['id' => $ticket->getId()]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getPropertyPath() . ': ' . $error->getMessage());
                }
            }
        }

        return $this->render('support/athlete/new.html.twig', [
            'ticket' => $ticket,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}', name: 'app_support_show', methods: ['GET', 'POST'])]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Check if the ticket belongs to the current user
        if ($ticket->getAthlete() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this ticket');
        }

        // Handle reply submission
        if ($request->isMethod('POST') && $request->request->has('reply_content')) {
            $replyContent = $request->request->get('reply_content');

            if (!empty(trim($replyContent))) {
                $reply = new TicketReply();
                $reply->setTicket($ticket);
                $reply->setAdmin($this->getUser()); // Store the athlete as admin for now (will be updated when admin replies)
                $reply->setContent($replyContent);

                $errors = $validator->validate($reply);
                if (count($errors) === 0) {
                    $ticket->setUpdatedAt(new \DateTime());
                    $entityManager->persist($reply);
                    $entityManager->flush();

                    $this->addFlash('success', 'Your reply has been posted!');
                    return $this->redirectToRoute('app_support_show', ['id' => $ticket->getId()]);
                }
            }
        }

        return $this->render('support/athlete/show.html.twig', [
            'ticket' => $ticket,
            'user' => $this->getUser(),
        ]);
    }
}
