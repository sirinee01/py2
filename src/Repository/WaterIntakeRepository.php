<?php
// src/Repository/WaterIntakeRepository.php

namespace App\Repository;

use App\Entity\WaterIntake;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WaterIntake>
 */
class WaterIntakeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WaterIntake::class);
    }

    public function findTodayByUser(User $user): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.consumedAt >= :today')
            ->andWhere('w.consumedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('w.consumedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalForToday(User $user): float
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $result = $this->createQueryBuilder('w')
            ->select('SUM(w.amount) as total')
            ->andWhere('w.user = :user')
            ->andWhere('w.consumedAt >= :today')
            ->andWhere('w.consumedAt < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result ? (float) $result : 0.0;
    }

    public function getWeeklyData(User $user): array
    {
        $weekAgo = new \DateTime('-7 days');
        
        $results = $this->createQueryBuilder('w')
            ->select('DATE(w.consumedAt) as date, SUM(w.amount) as total')
            ->andWhere('w.user = :user')
            ->andWhere('w.consumedAt >= :weekAgo')
            ->setParameter('user', $user)
            ->setParameter('weekAgo', $weekAgo)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
            
        $data = [];
        foreach ($results as $result) {
            $data[$result['date']] = (float) $result['total'];
        }
        
        return $data;
    }
}