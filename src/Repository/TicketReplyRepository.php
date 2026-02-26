<?php

namespace App\Repository;

use App\Entity\TicketReply;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketReply>
 */
class TicketReplyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketReply::class);
    }

    /**
     * Find all replies for a ticket
     */
    public function findByTicket($ticket)
    {
        return $this->createQueryBuilder('tr')
            ->andWhere('tr.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('tr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
