<?php

namespace App\Entity;

use App\Repository\MealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MealRepository::class)]
class Meal
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

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['breakfast', 'lunch', 'dinner', 'snack'])]
    private ?string $mealTime = 'lunch';

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 7)]
    private ?int $dayOfWeek = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'meals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    // FIX: Changed mappedBy to inversedBy since Meal is the owning side in this relationship
    #[ORM\ManyToMany(targetEntity: NutritionPlan::class, inversedBy: 'meals', cascade: ['persist'])]
    private Collection $nutritionPlans;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->nutritionPlans = new ArrayCollection();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
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

    public function getMealTime(): ?string
    {
        return $this->mealTime;
    }

    public function setMealTime(string $mealTime): static
    {
        $this->mealTime = $mealTime;
        return $this;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;
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
     * @return Collection<int, NutritionPlan>
     */
    public function getNutritionPlans(): Collection
    {
        return $this->nutritionPlans;
    }

    public function addNutritionPlan(NutritionPlan $nutritionPlan): static
    {
        if (!$this->nutritionPlans->contains($nutritionPlan)) {
            $this->nutritionPlans->add($nutritionPlan);
            // Ensure bidirectional consistency
            if (!$nutritionPlan->getMeals()->contains($this)) {
                $nutritionPlan->addMeal($this);
            }
        }
        return $this;
    }

    public function removeNutritionPlan(NutritionPlan $nutritionPlan): static
    {
        if ($this->nutritionPlans->removeElement($nutritionPlan)) {
            // Ensure bidirectional consistency
            if ($nutritionPlan->getMeals()->contains($this)) {
                $nutritionPlan->removeMeal($this);
            }
        }
        return $this;
    }

    // Helper method to get day name
    public function getDayName(): string
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday', 
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];
        
        return $days[$this->dayOfWeek] ?? 'Not specified';
    }
}