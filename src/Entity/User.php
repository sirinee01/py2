<?php

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

    /**
     * @var string The hashed password
     */
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // Nutrition plans created by this coach
    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: NutritionPlan::class)]
    private Collection $nutritionPlans;

    // Meals created by this coach
    #[ORM\OneToMany(mappedBy: 'coach', targetEntity: Meal::class)]
    private Collection $meals;

    // Competitions organized by this user (if admin)
    #[ORM\OneToMany(mappedBy: 'organizer', targetEntity: Competition::class)]
    private Collection $organizedCompetitions;

    // Competition applications by this user (if athlete)
    #[ORM\OneToMany(mappedBy: 'athlete', targetEntity: CompetitionApplication::class)]
    private Collection $competitionApplications;

    // Nutrition plan assigned to this user (if athlete)
    #[ORM\ManyToOne(targetEntity: NutritionPlan::class, inversedBy: 'athletes')]
    private ?NutritionPlan $assignedNutritionPlan = null;

    // Water intake tracking (for athletes)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $waterIntake = [];

    // Email verification fields
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ['default' => 0])]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $verificationCodeExpiresAt = null;
    // Password Reset fields
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordResetCode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $passwordResetCodeExpiresAt = null;
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->nutritionPlans = new ArrayCollection();
        $this->meals = new ArrayCollection();
        $this->organizedCompetitions = new ArrayCollection();
        $this->competitionApplications = new ArrayCollection();
        $this->waterIntake = [];
        $this->isVerified = false;
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
        
        // Add the roleType as a ROLE_ prefixed role
        $roles[] = 'ROLE_' . strtoupper($this->roleType);

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
            // set the owning side to null (unless already changed)
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
            // set the owning side to null (unless already changed)
            if ($meal->getCoach() === $this) {
                $meal->setCoach(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Competition>
     */
    public function getOrganizedCompetitions(): Collection
    {
        return $this->organizedCompetitions;
    }

    public function addOrganizedCompetition(Competition $organizedCompetition): static
    {
        if (!$this->organizedCompetitions->contains($organizedCompetition)) {
            $this->organizedCompetitions->add($organizedCompetition);
            $organizedCompetition->setOrganizer($this);
        }

        return $this;
    }

    public function removeOrganizedCompetition(Competition $organizedCompetition): static
    {
        if ($this->organizedCompetitions->removeElement($organizedCompetition)) {
            // set the owning side to null (unless already changed)
            if ($organizedCompetition->getOrganizer() === $this) {
                $organizedCompetition->setOrganizer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompetitionApplication>
     */
    public function getCompetitionApplications(): Collection
    {
        return $this->competitionApplications;
    }

    public function addCompetitionApplication(CompetitionApplication $competitionApplication): static
    {
        if (!$this->competitionApplications->contains($competitionApplication)) {
            $this->competitionApplications->add($competitionApplication);
            $competitionApplication->setAthlete($this);
        }

        return $this;
    }

    public function removeCompetitionApplication(CompetitionApplication $competitionApplication): static
    {
        if ($this->competitionApplications->removeElement($competitionApplication)) {
            // set the owning side to null (unless already changed)
            if ($competitionApplication->getAthlete() === $this) {
                $competitionApplication->setAthlete(null);
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

    public function getWaterIntake(): array
    {
        return $this->waterIntake;
    }

    public function setWaterIntake(array $waterIntake): static
    {
        $this->waterIntake = $waterIntake;

        return $this;
    }

    public function addWaterIntake(float $amount, \DateTimeInterface $date = null): static
    {
        $dateKey = ($date ?? new \DateTime())->format('Y-m-d');
        
        if (!isset($this->waterIntake[$dateKey])) {
            $this->waterIntake[$dateKey] = 0;
        }
        
        $this->waterIntake[$dateKey] += $amount;
        
        return $this;
    }

    public function getTodaysWaterIntake(): float
    {
        $today = (new \DateTime())->format('Y-m-d');
        return $this->waterIntake[$today] ?? 0;
    }

    // Helper method to get approved competitions
    public function getApprovedCompetitions(): array
    {
        $approved = [];
        foreach ($this->competitionApplications as $app) {
            if ($app->getStatus() === 'approved') {
                $approved[] = $app->getCompetition();
            }
        }
        return $approved;
    }

    // Email Verification getters and setters
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): static
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getVerificationCodeExpiresAt(): ?\DateTimeInterface
    {
        return $this->verificationCodeExpiresAt;
    }

    public function setVerificationCodeExpiresAt(?\DateTimeInterface $verificationCodeExpiresAt): static
    {
        $this->verificationCodeExpiresAt = $verificationCodeExpiresAt;
        return $this;
    }

    public function isVerificationCodeExpired(): bool
    {
        if (!$this->verificationCodeExpiresAt) {
            return true;
        }
        return new \DateTime() > $this->verificationCodeExpiresAt;
    }

    // Password Reset getters and setters
    public function getPasswordResetCode(): ?string
    {
        return $this->passwordResetCode;
    }

    public function setPasswordResetCode(?string $passwordResetCode): static
    {
        $this->passwordResetCode = $passwordResetCode;
        return $this;
    }

    public function getPasswordResetCodeExpiresAt(): ?\DateTimeInterface
    {
        return $this->passwordResetCodeExpiresAt;
    }

    public function setPasswordResetCodeExpiresAt(?\DateTimeInterface $passwordResetCodeExpiresAt): static
    {
        $this->passwordResetCodeExpiresAt = $passwordResetCodeExpiresAt;
        return $this;
    }

    public function isPasswordResetCodeExpired(): bool
    {
        if (!$this->passwordResetCodeExpiresAt) {
            return true;
        }
        return new \DateTime() > $this->passwordResetCodeExpiresAt;
    }
}
