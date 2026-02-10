<?php

namespace App\Repository;

use App\Entity\NutritionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NutritionPlan>
 */
class NutritionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NutritionPlan::class);
    }

    public function findByCoach($coach)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.coach = :coach')
            ->setParameter('coach', $coach)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}