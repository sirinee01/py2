<?php
// src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(['athlete', 'coach', 'admin'])]
    private ?string $roleType = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(['male', 'female', 'other', 'not_specified'])]
    private ?string $gender = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $onboardingCompleted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // Nutrition plans created by this coach
    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: NutritionPlan::class)]
    private Collection $nutritionPlans;

    // Meals created by this coach
    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: Meal::class)]
    private Collection $meals;

    // Nutrition plan assigned to this user (if athlete)
    #[ORM\ManyToOne(targetEntity: NutritionPlan::class, inversedBy: 'athletes')]
    private ?NutritionPlan $assignedNutritionPlan = null;

    // Meal consumption tracking
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MealConsumption::class, cascade: ['persist', 'remove'])]
    private Collection $mealConsumptions;

    // Water intake tracking
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WaterIntake::class, cascade: ['persist', 'remove'])]
    private Collection $waterIntakes;

    // Progress entries
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Progress::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['recordedAt' => 'DESC'])]
    private Collection $progressEntries;

    // Custom meal logs
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CustomMealLog::class, cascade: ['persist', 'remove'])]
    private Collection $customMealLogs;

    // ========== NEW: Conversations relationship ==========
    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants')]
    private Collection $conversations;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->nutritionPlans = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->mealConsumptions = new ArrayCollection();
        $this->waterIntakes = new ArrayCollection();
        $this->progressEntries = new ArrayCollection();
        $this->customMealLogs = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->onboardingCompleted = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        
        if ($this->roleType) {
            $roles[] = 'ROLE_' . strtoupper($this->roleType);
        }
        
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getRoleType(): ?string
    {
        return $this->roleType;
    }

    public function setRoleType(string $roleType): static
    {
        $this->roleType = $roleType;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function isOnboardingCompleted(): ?bool
    {
        return $this->onboardingCompleted;
    }

    public function setOnboardingCompleted(bool $onboardingCompleted): static
    {
        $this->onboardingCompleted = $onboardingCompleted;
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
            $nutritionPlan->setCoach($this);
        }
        return $this;
    }

    public function removeNutritionPlan(NutritionPlan $nutritionPlan): static
    {
        if ($this->nutritionPlans->removeElement($nutritionPlan)) {
            if ($nutritionPlan->getCoach() === $this) {
                $nutritionPlan->setCoach(null);
            }
        }
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
            $meal->setCoach($this);
        }
        return $this;
    }

    public function removeMeal(Meal $meal): static
    {
        if ($this->meals->removeElement($meal)) {
            if ($meal->getCoach() === $this) {
                $meal->setCoach(null);
            }
        }
        return $this;
    }

    public function getAssignedNutritionPlan(): ?NutritionPlan
    {
        return $this->assignedNutritionPlan;
    }

    public function setAssignedNutritionPlan(?NutritionPlan $assignedNutritionPlan): static
    {
        $this->assignedNutritionPlan = $assignedNutritionPlan;
        return $this;
    }

    /**
     * @return Collection<int, MealConsumption>
     */
    public function getMealConsumptions(): Collection
    {
        return $this->mealConsumptions;
    }

    public function addMealConsumption(MealConsumption $mealConsumption): static
    {
        if (!$this->mealConsumptions->contains($mealConsumption)) {
            $this->mealConsumptions->add($mealConsumption);
            $mealConsumption->setUser($this);
        }
        return $this;
    }

    public function removeMealConsumption(MealConsumption $mealConsumption): static
    {
        if ($this->mealConsumptions->removeElement($mealConsumption)) {
            if ($mealConsumption->getUser() === $this) {
                $mealConsumption->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, WaterIntake>
     */
    public function getWaterIntakes(): Collection
    {
        return $this->waterIntakes;
    }

    public function addWaterIntake(WaterIntake $waterIntake): static
    {
        if (!$this->waterIntakes->contains($waterIntake)) {
            $this->waterIntakes->add($waterIntake);
            $waterIntake->setUser($this);
        }
        return $this;
    }

    public function removeWaterIntake(WaterIntake $waterIntake): static
    {
        if ($this->waterIntakes->removeElement($waterIntake)) {
            if ($waterIntake->getUser() === $this) {
                $waterIntake->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Progress>
     */
    public function getProgressEntries(): Collection
    {
        return $this->progressEntries;
    }

    public function addProgressEntry(Progress $progress): static
    {
        if (!$this->progressEntries->contains($progress)) {
            $this->progressEntries->add($progress);
            $progress->setUser($this);
        }
        return $this;
    }

    public function removeProgressEntry(Progress $progress): static
    {
        if ($this->progressEntries->removeElement($progress)) {
            if ($progress->getUser() === $this) {
                $progress->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, CustomMealLog>
     */
    public function getCustomMealLogs(): Collection
    {
        return $this->customMealLogs;
    }

    public function addCustomMealLog(CustomMealLog $customMealLog): static
    {
        if (!$this->customMealLogs->contains($customMealLog)) {
            $this->customMealLogs->add($customMealLog);
            $customMealLog->setUser($this);
        }
        return $this;
    }

    public function removeCustomMealLog(CustomMealLog $customMealLog): static
    {
        if ($this->customMealLogs->removeElement($customMealLog)) {
            if ($customMealLog->getUser() === $this) {
                $customMealLog->setUser(null);
            }
        }
        return $this;
    }

    // ========== NEW: Conversation methods ==========

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->addParticipant($this);
        }
        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            $conversation->removeParticipant($this);
        }
        return $this;
    }

    // ========== HELPER METHODS ==========

    /**
     * Get today's meal consumptions
     */
    public function getTodaysMealConsumptions(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $consumptions = [];
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $consumptions[] = $consumption;
            }
        }
        return $consumptions;
    }

    /**
     * Get today's water intakes
     */
    public function getTodaysWaterIntakes(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $intakes = [];
        foreach ($this->waterIntakes as $intake) {
            if ($intake->getConsumedAt() >= $today && $intake->getConsumedAt() < $tomorrow) {
                $intakes[] = $intake;
            }
        }
        return $intakes;
    }

    /**
     * Get total water intake for today
     */
    public function getTodaysTotalWater(): float
    {
        $total = 0;
        foreach ($this->getTodaysWaterIntakes() as $intake) {
            $total += $intake->getAmount();
        }
        return round($total, 2);
    }

    /**
     * Check if a meal was consumed today
     */
    public function isMealConsumedToday(Meal $meal): bool
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getMeal() === $meal && 
                $consumption->getConsumedAt() >= $today && 
                $consumption->getConsumedAt() < $tomorrow) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get today's custom meals
     */
    public function getTodaysCustomMeals(): array
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        $customMeals = [];
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $customMeals[] = $customMeal;
            }
        }
        return $customMeals;
    }

    /**
     * Get today's consumed calories (includes custom meals)
     */
    public function getTodaysCalories(): int
    {
        $total = 0;
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        // From plan meals
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $total += $consumption->getMeal()->getCalories() * ($consumption->getServings() ?? 1);
            }
        }
        
        // From custom meals
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $total += $customMeal->getCalories();
            }
        }
        
        return $total;
    }

    /**
     * Get today's consumed protein (includes custom meals)
     */
    public function getTodaysProtein(): int
    {
        $total = 0;
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        // From plan meals
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $total += ($consumption->getMeal()->getProtein() ?? 0) * ($consumption->getServings() ?? 1);
            }
        }
        
        // From custom meals
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $total += $customMeal->getProtein() ?? 0;
            }
        }
        
        return $total;
    }

    /**
     * Get today's consumed carbs (includes custom meals)
     */
    public function getTodaysCarbs(): int
    {
        $total = 0;
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        // From plan meals
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $total += ($consumption->getMeal()->getCarbs() ?? 0) * ($consumption->getServings() ?? 1);
            }
        }
        
        // From custom meals
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $total += $customMeal->getCarbs() ?? 0;
            }
        }
        
        return $total;
    }

    /**
     * Get today's consumed fat (includes custom meals)
     */
    public function getTodaysFat(): int
    {
        $total = 0;
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        // From plan meals
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $total += ($consumption->getMeal()->getFat() ?? 0) * ($consumption->getServings() ?? 1);
            }
        }
        
        // From custom meals
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $total += $customMeal->getFat() ?? 0;
            }
        }
        
        return $total;
    }

    /**
     * Get all today's meals grouped by meal time
     */
    public function getTodaysMealConsumptionsGrouped(): array
    {
        $grouped = [];
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        
        // Group plan meals
        foreach ($this->mealConsumptions as $consumption) {
            if ($consumption->getConsumedAt() >= $today && $consumption->getConsumedAt() < $tomorrow) {
                $mealTime = $consumption->getMeal()->getMealTime();
                if (!isset($grouped[$mealTime])) {
                    $grouped[$mealTime] = [];
                }
                $grouped[$mealTime][] = [
                    'type' => 'plan',
                    'id' => $consumption->getMeal()->getId(),
                    'name' => $consumption->getMeal()->getName(),
                    'description' => $consumption->getMeal()->getDescription(),
                    'calories' => $consumption->getMeal()->getCalories() * ($consumption->getServings() ?? 1),
                    'protein' => ($consumption->getMeal()->getProtein() ?? 0) * ($consumption->getServings() ?? 1),
                    'carbs' => ($consumption->getMeal()->getCarbs() ?? 0) * ($consumption->getServings() ?? 1),
                    'fat' => ($consumption->getMeal()->getFat() ?? 0) * ($consumption->getServings() ?? 1),
                    'servings' => $consumption->getServings() ?? 1,
                    'consumptionId' => $consumption->getId(),
                    'image' => $consumption->getMeal()->getImage(),
                    'time' => $consumption->getConsumedAt(),
                    'consumed' => true
                ];
            }
        }
        
        // Group custom meals
        foreach ($this->customMealLogs as $customMeal) {
            if ($customMeal->getConsumedAt() >= $today && $customMeal->getConsumedAt() < $tomorrow) {
                $mealTime = $customMeal->getMealTime();
                if (!isset($grouped[$mealTime])) {
                    $grouped[$mealTime] = [];
                }
                $grouped[$mealTime][] = [
                    'type' => 'custom',
                    'id' => $customMeal->getId(),
                    'name' => $customMeal->getName(),
                    'description' => $customMeal->getDescription(),
                    'calories' => $customMeal->getCalories(),
                    'protein' => $customMeal->getProtein() ?? 0,
                    'carbs' => $customMeal->getCarbs() ?? 0,
                    'fat' => $customMeal->getFat() ?? 0,
                    'reason' => $customMeal->getReason(),
                    'time' => $customMeal->getConsumedAt(),
                    'mealTime' => $mealTime,
                    'consumed' => true,
                    'image' => null
                ];
            }
        }
        
        // Sort each meal time by time
        foreach ($grouped as &$meals) {
            usort($meals, function($a, $b) {
                return $a['time'] <=> $b['time'];
            });
        }
        
        return $grouped;
    }

    /**
     * Get the latest progress entry
     */
    public function getLatestProgress(): ?Progress
    {
        return $this->progressEntries->isEmpty() ? null : $this->progressEntries->first();
    }

    /**
     * Get the initial onboarding progress
     */
    public function getOnboardingProgress(): ?Progress
    {
        foreach ($this->progressEntries as $progress) {
            if ($progress->isIsInitialOnboarding()) {
                return $progress;
            }
        }
        return null;
    }

    /**
     * Calculate age from birthdate
     */
    public function getAge(): ?int
    {
        if (!$this->birthDate) {
            return null;
        }
        $now = new \DateTime();
        return $now->diff($this->birthDate)->y;
    }

    /**
     * Check if user needs to complete onboarding
     */
    public function needsOnboarding(): bool
    {
        return !$this->onboardingCompleted && $this->roleType === 'athlete';
    }

    /**
     * Get user's full name (alias for getName)
     */
    public function getFullName(): ?string
    {
        return $this->name;
    }
}