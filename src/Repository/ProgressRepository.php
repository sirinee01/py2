<?php
// src/Repository/ProgressRepository.php

namespace App\Repository;

use App\Entity\Progress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Progress>
 */
class ProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progress::class);
    }

    /**
     * Find progress entries for a user ordered by date
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.recordedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get progress trend (last 7 entries)
     */
    public function getProgressTrend(User $user, int $limit = 7): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.recordedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get weight history for charts
     */
    public function getWeightHistory(User $user, int $days = 30): array
    {
        $since = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.recordedAt >= :since')
            ->andWhere('p.weight IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('p.recordedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has completed onboarding
     */
    public function hasCompletedOnboarding(User $user): bool
    {
        $onboardingEntry = $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.isInitialOnboarding = true')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $onboardingEntry !== null;
    }
}