<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Find all tickets by athlete
     */
    public function findByAthlete($athlete)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.athlete = :athlete')
            ->setParameter('athlete', $athlete)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all open tickets (for admin)
     */
    public function findAllOpen()
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', ['open', 'in-progress'])
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all tickets for admin
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count open tickets
     */
    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('statuses', ['open', 'in-progress'])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
