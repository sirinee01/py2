<?php
// src/Entity/NutritionPlan.php

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

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $dailyWaterIntake = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    #[ORM\ManyToMany(targetEntity: Meal::class, inversedBy: 'nutritionPlans', cascade: ['persist'])]
    private Collection $meals;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'assignedNutritionPlan')]
    private Collection $athletes;

    // Daily targets
    #[ORM\Column(nullable: true)]
    private ?int $targetCalories = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetProtein = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetCarbs = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetFat = null;

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
            if (!$meal->getNutritionPlans()->contains($this)) {
                $meal->addNutritionPlan($this);
            }
        }
        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        if ($this->meals->removeElement($meal)) {
            if ($meal->getNutritionPlans()->contains($this)) {
                $meal->removeNutritionPlan($this);
            }
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
            if ($athlete->getAssignedNutritionPlan() === $this) {
                $athlete->setAssignedNutritionPlan(null);
            }
        }
        return $this;
    }

    public function getTargetCalories(): ?int
    {
        return $this->targetCalories;
    }

    public function setTargetCalories(?int $targetCalories): static
    {
        $this->targetCalories = $targetCalories;
        return $this;
    }

    public function getTargetProtein(): ?int
    {
        return $this->targetProtein;
    }

    public function setTargetProtein(?int $targetProtein): static
    {
        $this->targetProtein = $targetProtein;
        return $this;
    }

    public function getTargetCarbs(): ?int
    {
        return $this->targetCarbs;
    }

    public function setTargetCarbs(?int $targetCarbs): static
    {
        $this->targetCarbs = $targetCarbs;
        return $this;
    }

    public function getTargetFat(): ?int
    {
        return $this->targetFat;
    }

    public function setTargetFat(?int $targetFat): static
    {
        $this->targetFat = $targetFat;
        return $this;
    }

    /**
     * Get total calories from all meals in the plan
     */
    public function getTotalCalories(): int
    {
        $total = 0;
        foreach ($this->meals as $meal) {
            $total += $meal->getCalories();
        }
        return $total;
    }

    /**
     * Get meals for a specific day of the week
     */
    public function getMealsByDay(int $dayOfWeek): array
    {
        $dayMeals = [];
        foreach ($this->meals as $meal) {
            if ($meal->getDayOfWeek() == $dayOfWeek) {
                $dayMeals[] = $meal;
            }
        }
        
        // Sort by meal time
        usort($dayMeals, function($a, $b) {
            $order = ['breakfast' => 1, 'lunch' => 2, 'dinner' => 3, 'snack' => 4];
            return ($order[$a->getMealTime()] ?? 5) <=> ($order[$b->getMealTime()] ?? 5);
        });
        
        return $dayMeals;
    }

    /**
     * Get today's meals
     */
    public function getTodaysMeals(): array
    {
        $today = (int) date('N'); // 1 (Monday) to 7 (Sunday)
        return $this->getMealsByDay($today);
    }

    /**
     * Calculate daily targets based on meals
     */
    public function calculateDailyTargets(): array
    {
        $todaysMeals = $this->getTodaysMeals();
        
        $calories = 0;
        $protein = 0;
        $carbs = 0;
        $fat = 0;
        
        foreach ($todaysMeals as $meal) {
            $calories += $meal->getCalories();
            $protein += $meal->getProtein() ?? 0;
            $carbs += $meal->getCarbs() ?? 0;
            $fat += $meal->getFat() ?? 0;
        }
        
        return [
            'calories' => $calories,
            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,
            'water' => $this->dailyWaterIntake ?? 2.5
        ];
    }
}