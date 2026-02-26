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

#[Route('/admin/support')]
#[IsGranted('ROLE_ADMIN')]
class AdminSupportController extends AbstractController
{
    #[Route('/', name: 'app_admin_support_index')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAll();
        $openCount = $ticketRepository->countOpen();

        return $this->render('support/admin/index.html.twig', [
            'tickets' => $tickets,
            'openCount' => $openCount,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_support_show', methods: ['GET', 'POST'])]
    public function show(Ticket $ticket, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Handle reply submission
        if ($request->isMethod('POST') && $request->request->has('reply_content')) {
            $replyContent = $request->request->get('reply_content');
            $status = $request->request->get('status');

            if (!empty(trim($replyContent))) {
                $reply = new TicketReply();
                $reply->setTicket($ticket);
                $reply->setAdmin($this->getUser());
                $reply->setContent($replyContent);

                $errors = $validator->validate($reply);
                if (count($errors) === 0) {
                    // Update ticket status and updated_at
                    $ticket->setStatus($status);
                    $ticket->setUpdatedAt(new \DateTime());
                    $ticket->setAdmin($this->getUser());

                    $entityManager->persist($reply);
                    $entityManager->flush();

                    $this->addFlash('success', 'Your reply has been sent!');
                    return $this->redirectToRoute('app_admin_support_show', ['id' => $ticket->getId()]);
                } else {
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error->getMessage());
                    }
                }
            } else {
                // Update status even if no reply
                if ($status !== $ticket->getStatus()) {
                    $ticket->setStatus($status);
                    $ticket->setUpdatedAt(new \DateTime());
                    $entityManager->flush();
                    $this->addFlash('success', 'Ticket status updated!');
                    return $this->redirectToRoute('app_admin_support_show', ['id' => $ticket->getId()]);
                }
            }
        }

        return $this->render('support/admin/show.html.twig', [
            'ticket' => $ticket,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_support_update_status', methods: ['POST'])]
    public function updateStatus(Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response
    {
        $status = $request->request->get('status');

        if (in_array($status, ['open', 'in-progress', 'closed'])) {
            $ticket->setStatus($status);
            $ticket->setUpdatedAt(new \DateTime());
            $ticket->setAdmin($this->getUser());
            $entityManager->flush();

            $this->addFlash('success', 'Ticket status updated to ' . ucfirst($status));
        }

        return $this->redirectToRoute('app_admin_support_show', ['id' => $ticket->getId()]);
    }
}
