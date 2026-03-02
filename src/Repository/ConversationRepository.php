<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Find existing conversation between two users.
     */
    public function findOneByParticipants(User $user1, User $user2): ?Conversation
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :user1 OR p.id = :user2')
            ->groupBy('c.id')
            ->having('COUNT(c.id) = 2');

        $result = $qb->getQuery()
            ->setParameter('user1', $user1->getId())
            ->setParameter('user2', $user2->getId())
            ->getResult();

        return $result[0] ?? null;
    }

    /**
     * Get all conversations for a user, ordered by last message.
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p')
            ->leftJoin('c.messages', 'm')
            ->where('p.id = :userId')
            ->orderBy('c.updatedAt', 'DESC')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    }
}