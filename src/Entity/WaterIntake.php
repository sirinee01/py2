<?php
// src/Entity/WaterIntake.php

namespace App\Entity;

use App\Repository\WaterIntakeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WaterIntakeRepository::class)]
class WaterIntake
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'waterIntakes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'float')]
    #[Assert\Positive]
    private ?float $amount = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $consumedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit = 'L';

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

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
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

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }
}