<?php
// src/Entity/Progress.php

namespace App\Entity;

use App\Repository\ProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgressRepository::class)]
class Progress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'progressEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $recordedAt = null;

    // Physical Measurements
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?float $weight = null; // in kg

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?float $height = null; // in cm

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $age = null;

    // Body Composition
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $bodyFatPercentage = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $muscleMass = null; // in kg

    // Nutrition Tracking
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $dailyCalorieIntake = null; // calories they consume

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $dailyWaterIntake = null; // in liters

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $proteinIntake = null; // in grams

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $carbIntake = null; // in grams

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $fatIntake = null; // in grams

    // Fitness Level
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(['sedentary', 'lightly_active', 'moderately_active', 'very_active', 'extra_active'])]
    private ?string $activityLevel = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $workoutsPerWeek = null;

    // Goals
    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(['weight_loss', 'muscle_gain', 'maintenance', 'endurance', 'strength'])]
    private ?string $primaryGoal = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $targetWeight = null; // in kg

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(['per_week', 'per_month', 'per_3_months', 'per_6_months'])]
    private ?string $goalTimeline = null;

    // Calculated Fields (not persisted, just for calculations)
    private ?float $bmi = null;
    private ?int $bmr = null; // Basal Metabolic Rate
    private ?int $tdee = null; // Total Daily Energy Expenditure
    private ?int $recommendedCalories = null;

    // Medical/Health Info
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dietaryRestrictions = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $healthConditions = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $measurements = []; // For additional custom measurements (chest, waist, arms, etc.)

    #[ORM\Column]
    private bool $isInitialOnboarding = false; // True for first entry, false for regular updates

    public function __construct()
    {
        $this->recordedAt = new \DateTime();
        $this->measurements = [];
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

    public function getRecordedAt(): ?\DateTimeInterface
    {
        return $this->recordedAt;
    }

    public function setRecordedAt(\DateTimeInterface $recordedAt): static
    {
        $this->recordedAt = $recordedAt;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getBodyFatPercentage(): ?float
    {
        return $this->bodyFatPercentage;
    }

    public function setBodyFatPercentage(?float $bodyFatPercentage): static
    {
        $this->bodyFatPercentage = $bodyFatPercentage;
        return $this;
    }

    public function getMuscleMass(): ?float
    {
        return $this->muscleMass;
    }

    public function setMuscleMass(?float $muscleMass): static
    {
        $this->muscleMass = $muscleMass;
        return $this;
    }

    public function getDailyCalorieIntake(): ?int
    {
        return $this->dailyCalorieIntake;
    }

    public function setDailyCalorieIntake(?int $dailyCalorieIntake): static
    {
        $this->dailyCalorieIntake = $dailyCalorieIntake;
        return $this;
    }

    public function getDailyWaterIntake(): ?float
    {
        return $this->dailyWaterIntake;
    }

    public function setDailyWaterIntake(?float $dailyWaterIntake): static
    {
        $this->dailyWaterIntake = $dailyWaterIntake;
        return $this;
    }

    public function getProteinIntake(): ?int
    {
        return $this->proteinIntake;
    }

    public function setProteinIntake(?int $proteinIntake): static
    {
        $this->proteinIntake = $proteinIntake;
        return $this;
    }

    public function getCarbIntake(): ?int
    {
        return $this->carbIntake;
    }

    public function setCarbIntake(?int $carbIntake): static
    {
        $this->carbIntake = $carbIntake;
        return $this;
    }

    public function getFatIntake(): ?int
    {
        return $this->fatIntake;
    }

    public function setFatIntake(?int $fatIntake): static
    {
        $this->fatIntake = $fatIntake;
        return $this;
    }

    public function getActivityLevel(): ?string
    {
        return $this->activityLevel;
    }

    public function setActivityLevel(?string $activityLevel): static
    {
        $this->activityLevel = $activityLevel;
        return $this;
    }

    public function getWorkoutsPerWeek(): ?int
    {
        return $this->workoutsPerWeek;
    }

    public function setWorkoutsPerWeek(?int $workoutsPerWeek): static
    {
        $this->workoutsPerWeek = $workoutsPerWeek;
        return $this;
    }

    public function getPrimaryGoal(): ?string
    {
        return $this->primaryGoal;
    }

    public function setPrimaryGoal(?string $primaryGoal): static
    {
        $this->primaryGoal = $primaryGoal;
        return $this;
    }

    public function getTargetWeight(): ?float
    {
        return $this->targetWeight;
    }

    public function setTargetWeight(?float $targetWeight): static
    {
        $this->targetWeight = $targetWeight;
        return $this;
    }

    public function getGoalTimeline(): ?string
    {
        return $this->goalTimeline;
    }

    public function setGoalTimeline(?string $goalTimeline): static
    {
        $this->goalTimeline = $goalTimeline;
        return $this;
    }

    public function getDietaryRestrictions(): ?string
    {
        return $this->dietaryRestrictions;
    }

    public function setDietaryRestrictions(?string $dietaryRestrictions): static
    {
        $this->dietaryRestrictions = $dietaryRestrictions;
        return $this;
    }

    public function getHealthConditions(): ?string
    {
        return $this->healthConditions;
    }

    public function setHealthConditions(?string $healthConditions): static
    {
        $this->healthConditions = $healthConditions;
        return $this;
    }

    public function getMeasurements(): array
    {
        return $this->measurements;
    }

    public function setMeasurements(array $measurements): static
    {
        $this->measurements = $measurements;
        return $this;
    }

    public function isIsInitialOnboarding(): bool
    {
        return $this->isInitialOnboarding;
    }

    public function setIsInitialOnboarding(bool $isInitialOnboarding): static
    {
        $this->isInitialOnboarding = $isInitialOnboarding;
        return $this;
    }

    // ========== CALCULATED METHODS ==========

    /**
     * Calculate BMI (Body Mass Index)
     * BMI = weight(kg) / height(m)²
     */
    public function getBmi(): ?float
    {
        if (!$this->weight || !$this->height) {
            return null;
        }
        
        $heightInMeters = $this->height / 100;
        return round($this->weight / ($heightInMeters * $heightInMeters), 1);
    }

    /**
     * Get BMI Category
     */
    public function getBmiCategory(): ?string
    {
        $bmi = $this->getBmi();
        if (!$bmi) return null;
        
        return match(true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25 => 'normal',
            $bmi < 30 => 'overweight',
            default => 'obese'
        };
    }

    /**
     * Calculate BMR (Basal Metabolic Rate) using Mifflin-St Jeor Equation
     */
    public function getBmr(): ?int
    {
        if (!$this->weight || !$this->height || !$this->age || !$this->user) {
            return null;
        }

        // Get gender from user (assuming you have a gender field)
        $gender = $this->user->getGender() ?? 'male'; // Default to male if not set
        
        if ($gender === 'male') {
            // Men: BMR = 10 * weight(kg) + 6.25 * height(cm) - 5 * age + 5
            return (int) round((10 * $this->weight) + (6.25 * $this->height) - (5 * $this->age) + 5);
        } else {
            // Women: BMR = 10 * weight(kg) + 6.25 * height(cm) - 5 * age - 161
            return (int) round((10 * $this->weight) + (6.25 * $this->height) - (5 * $this->age) - 161);
        }
    }

    /**
     * Calculate TDEE (Total Daily Energy Expenditure)
     * TDEE = BMR * Activity Factor
     */
    public function getTdee(): ?int
    {
        $bmr = $this->getBmr();
        if (!$bmr || !$this->activityLevel) {
            return null;
        }

        $activityFactors = [
            'sedentary' => 1.2,        // Little or no exercise
            'lightly_active' => 1.375,  // Light exercise 1-3 days/week
            'moderately_active' => 1.55, // Moderate exercise 3-5 days/week
            'very_active' => 1.725,      // Hard exercise 6-7 days/week
            'extra_active' => 1.9         // Very hard exercise & physical job
        ];

        $factor = $activityFactors[$this->activityLevel] ?? 1.2;
        return (int) round($bmr * $factor);
    }

    /**
     * Get Recommended Calorie Intake based on goal
     */
    public function getRecommendedCalories(): ?int
    {
        $tdee = $this->getTdee();
        if (!$tdee || !$this->primaryGoal) {
            return null;
        }

        return match($this->primaryGoal) {
            'weight_loss' => $tdee - 500,      // 500 calorie deficit
            'muscle_gain' => $tdee + 300,       // 300 calorie surplus
            'maintenance' => $tdee,
            'endurance' => $tdee + 200,
            'strength' => $tdee + 250,
            default => $tdee
        };
    }

    /**
     * Calculate progress percentage towards goal
     */
    public function getGoalProgress(): ?float
    {
        if (!$this->primaryGoal || !$this->targetWeight || !$this->weight) {
            return null;
        }

        $previousProgress = $this->user?->getLatestProgressBefore($this->recordedAt);
        if (!$previousProgress || !$previousProgress->getWeight()) {
            return 0;
        }

        $startWeight = $previousProgress->getWeight();
        $currentWeight = $this->weight;
        $targetWeight = $this->targetWeight;

        if ($targetWeight > $startWeight) {
            // Weight gain goal
            $totalToGain = $targetWeight - $startWeight;
            $gained = $currentWeight - $startWeight;
            return min(100, round(($gained / $totalToGain) * 100, 1));
        } else {
            // Weight loss goal
            $totalToLose = $startWeight - $targetWeight;
            $lost = $startWeight - $currentWeight;
            return min(100, round(($lost / $totalToLose) * 100, 1));
        }
    }
}