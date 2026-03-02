<?php
// src/Repository/CustomMealLogRepository.php

namespace App\Repository;

use App\Entity\CustomMealLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomMealLog>
 */
class CustomMealLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomMealLog::class);
    }

    public function findByUserAndDateRange($user, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.consumedAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('c.consumedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}