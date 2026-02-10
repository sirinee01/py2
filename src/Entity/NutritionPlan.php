<?php

namespace App\Entity;

use App\Repository\NutritionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NutritionPlanRepository::class)]
class NutritionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $duration = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $objective = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // Daily water intake in liters
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $dailyWaterIntake = null;

    // Relationship with User (coach)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    // Relationship with Meal (ManyToMany)
    #[ORM\ManyToMany(targetEntity: Meal::class, inversedBy: 'nutritionPlans')]
    private Collection $meals;

    // Relationship with User (athletes) - ManyToMany
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'assignedNutritionPlan')]
    private Collection $athletes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->meals = new ArrayCollection();
        $this->athletes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(string $objective): static
    {
        $this->objective = $objective;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDailyWaterIntake(): ?int
    {
        return $this->dailyWaterIntake;
    }

    public function setDailyWaterIntake(?int $dailyWaterIntake): static
    {
        $this->dailyWaterIntake = $dailyWaterIntake;

        return $this;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    /**
     * @return Collection<int, Meal>
     */
    public function getMeals(): Collection
    {
        return $this->meals;
    }

    public function addMeal(Meal $meal): static
    {
        if (!$this->meals->contains($meal)) {
            $this->meals->add($meal);
            $meal->addNutritionPlan($this);
        }

        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        if ($this->meals->removeElement($meal)) {
            $meal->removeNutritionPlan($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAthletes(): Collection
    {
        return $this->athletes;
    }

    public function addAthlete(User $athlete): static
    {
        if (!$this->athletes->contains($athlete)) {
            $this->athletes->add($athlete);
            $athlete->setAssignedNutritionPlan($this);
        }

        return $this;
    }

    public function removeAthlete(User $athlete): static
    {
        if ($this->athletes->removeElement($athlete)) {
            // set the owning side to null (unless already changed)
            if ($athlete->getAssignedNutritionPlan() === $this) {
                $athlete->setAssignedNutritionPlan(null);
            }
        }

        return $this;
    }

    // Helper method to get today's meals
    public function getTodaysMeals(): Collection
    {
        $today = date('N'); // 1 (Monday) through 7 (Sunday)
        $todaysMeals = new ArrayCollection();
        
        foreach ($this->meals as $meal) {
            if ($meal->getDayOfWeek() == $today) {
                $todaysMeals->add($meal);
            }
        }
        
        return $todaysMeals;
    }
}