<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * The below length depends on the "algorithm" you use for hashing
     * the password, but this works well with bcrypt.
     *
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $role;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTimeInterface")
     */
    private $registerDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Category", mappedBy="user", cascade={"remove"})
     */
    private $categories;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BankAccount", mappedBy="user", cascade={"remove"}, orphanRemoval=true)
     */
    private $bankAccounts;

    public function __construct()
    {
        $this->isActive = true;
        // TODO add the user's Timezone
        $this->registerDate = new \DateTime();
        $this->categories = new ArrayCollection();
        $this->bankAccounts = new ArrayCollection();
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid('', true));
    }

    public function getId()
    {
        return $this->id;
    }

    // Register date (= now())
    public function getRegisterDate(): ?\DateTimeInterface
    {
        return $this->registerDate;
    }

    // Email
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    // Username
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUserIdentifier()
    {
        return $this->username;
    }

    // Pain password
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    // Password
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword($password)
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
    public function getRoles()
    {
        return array(empty($this->role) ? 'ROLE_USER' : $this->role);
    }

    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    // Active/lock/expired methods
    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
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

    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;
    }

    // Credentials & Serialize
    public function eraseCredentials()
    {
    }

    /**
     * @return Collection|Category[]
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
     * @return Collection|BankAccount[]
     */
    public function getBankAccounts(): Collection
    {
        return $this->bankAccounts;
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

    public function getDefaultBankAccount()
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('is_default', true));

        $matches = $this->bankAccounts->matching($criteria);

        return (count($matches) > 0) ? $matches->first() : null;
    }
}
