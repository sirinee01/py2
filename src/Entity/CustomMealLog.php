<?php
// src/Entity/CustomMealLog.php

namespace App\Entity;

use App\Repository\CustomMealLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomMealLogRepository::class)]
class CustomMealLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customMealLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $calories = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $protein = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $carbs = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $fat = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $consumedAt = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['breakfast', 'lunch', 'dinner', 'snack'])]
    private ?string $mealTime = 'lunch';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason = null;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): static
    {
        $this->calories = $calories;
        return $this;
    }

    public function getProtein(): ?int
    {
        return $this->protein;
    }

    public function setProtein(?int $protein): static
    {
        $this->protein = $protein;
        return $this;
    }

    public function getCarbs(): ?int
    {
        return $this->carbs;
    }

    public function setCarbs(?int $carbs): static
    {
        $this->carbs = $carbs;
        return $this;
    }

    public function getFat(): ?int
    {
        return $this->fat;
    }

    public function setFat(?int $fat): static
    {
        $this->fat = $fat;
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

    public function getMealTime(): ?string
    {
        return $this->mealTime;
    }

    public function setMealTime(string $mealTime): static
    {
        $this->mealTime = $mealTime;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }
}