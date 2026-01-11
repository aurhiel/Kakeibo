<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 25, unique: true)]
    private string $username;

    #[ORM\Column(type: Types::STRING, length: 190, unique: true)]
    #[Assert\Email()]
    private string $email;

    #[Assert\NotBlank()]
    #[Assert\Length(max: 4096)]
    private ?string $plainPassword;

    /**
     * The below length depends on the "algorithm" you use for hashing
     * the password, but this works well with bcrypt.
     */
    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $password;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $role;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank()]
    #[Assert\Type('\DateTimeInterface')]
    private \DateTimeInterface $registerDate;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Category::class, cascade: ['remove'])]
    private Collection $categories;

    /**
     * @var Collection<int, BankAccount>&ArrayCollection<int, BankAccount>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BankAccount::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $bankAccounts;

    public function __construct()
    {
        // TODO add the user's Timezone
        $this->registerDate = new \DateTime();
        $this->categories = new ArrayCollection();
        $this->bankAccounts = new ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid('', true));
    }

    public function getId(): int
    {
        return $this->id;
    }

    // Register date (= now())
    public function getRegisterDate(): ?\DateTimeInterface
    {
        return $this->registerDate;
    }

    // Email
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    // Username
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    // Pain password
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    // Password
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword($password): self
    {
        $this->password = $password;

        return $this;
    }

    // Salt
    public function getSalt()
    {
        return null;
    }

    // Roles
    public function getRoles(): array
    {
        return [!isset($this->role) || ($this->role === '' || $this->role === '0') ? 'ROLE_USER' : $this->role];
    }

    public function setRole($role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    // Active/lock/expired methods
    public function isAccountNonExpired(): bool
    {
        return true;
    }

    public function isAccountNonLocked(): bool
    {
        return true;
    }

    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    // Credentials & Serialize
    public function eraseCredentials()
    {
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setUser($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
            // set the owning side to null (unless already changed)
            if ($category->getUser() === $this) {
                $category->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BankAccount>
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
    }

    /**
     * @return Collection|BankAccount[]
     */
    public function getBankAccountsActive(): Collection
    {
        return $this->bankAccounts->filter(static fn (BankAccount $bankAccount) => false === $bankAccount->isArchived());
    }

    public function addBankAccount(BankAccount $bankAccount): self
    {
        if (!$this->bankAccounts->contains($bankAccount)) {
            $this->bankAccounts[] = $bankAccount;
            $bankAccount->setUser($this);
        }

        return $this;
    }

    public function removeBankAccount(BankAccount $bankAccount): self
    {
        if ($this->bankAccounts->contains($bankAccount)) {
            $this->bankAccounts->removeElement($bankAccount);
            // set the owning side to null (unless already changed)
            if ($bankAccount->getUser() === $this) {
                $bankAccount->setUser(null);
            }
        }

        return $this;
    }

    public function hasManyBankAccounts(): bool
    {
        return count($this->getBankAccounts()) > 1;
    }

    public function getDefaultBankAccount(): ?BankAccount
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('is_default', true))
            ->setMaxResults(1);

        $matches = $this->bankAccounts->matching($criteria);

        return $matches->isEmpty() ? null : $matches->first();
    }
}
