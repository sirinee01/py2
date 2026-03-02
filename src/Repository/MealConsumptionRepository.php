<?php
// src/Repository/MealConsumptionRepository.php

namespace App\Repository;

use App\Entity\MealConsumption;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MealConsumption>
 */
class MealConsumptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MealConsumption::class);
    }

    public function findTodayByUser(User $user): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.consumedAt >= :today')
            ->andWhere('m.consumedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('m.consumedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConsumedMeal(User $user, int $mealId, \DateTime $date): ?MealConsumption
    {
        $start = clone $date->modify('midnight');
        $end = clone $start->modify('+1 day');
        
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.meal = :mealId')
            ->andWhere('m.consumedAt >= :start')
            ->andWhere('m.consumedAt < :end')
            ->setParameter('user', $user)
            ->setParameter('mealId', $mealId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getOneOrNullResult();
    }
}