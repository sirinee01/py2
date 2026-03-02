<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Mark all messages in a conversation as read for a given user.
     */
    public function markAllAsRead(Conversation $conversation, User $user): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', true)
            ->where('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = false')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Count unread messages for a user across all conversations.
     */
    public function countUnreadForUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.conversation', 'c')
            ->innerJoin('c.participants', 'p')
            ->where('p.id = :userId')
            ->andWhere('m.sender != :userId')
            ->andWhere('m.isRead = false')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}