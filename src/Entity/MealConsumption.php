<?php
// src/Entity/MealConsumption.php

namespace App\Entity;

use App\Repository\MealConsumptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MealConsumptionRepository::class)]
class MealConsumption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mealConsumptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Meal $meal = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $consumedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $completed = true;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $servings = 1;

    public function __construct()
    {
        $this->consumedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getMeal(): ?Meal
    {
        return $this->meal;
    }

    public function setMeal(?Meal $meal): static
    {
        $this->meal = $meal;
        return $this;
    }

    public function getConsumedAt(): ?\DateTimeInterface
    {
        return $this->consumedAt;
    }

    public function setConsumedAt(\DateTimeInterface $consumedAt): static
    {
        $this->consumedAt = $consumedAt;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;
        return $this;
    }

    public function getServings(): ?int
    {
        return $this->servings;
    }

    public function setServings(?int $servings): static
    {
        $this->servings = $servings;
        return $this;
    }
}