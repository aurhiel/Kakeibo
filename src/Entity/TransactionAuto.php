<?php

namespace App\Entity;

use App\Repository\TransactionAutoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TransactionAutoRepository::class)
 */
class TransactionAuto
{
    // RT = Repeat Type
    const RT_YEARLY   = 'YEARLY';
    const RT_MONTHLY  = 'MONTHLY';
    const RT_WEEKLY   = 'WEEKLY';
    const RT_DAILY    = 'DAILY';

    const RT_LIST     = [
      self::RT_YEARLY,
      self::RT_MONTHLY,
      self::RT_WEEKLY,
      self::RT_DAILY,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity=BankAccount::class, inversedBy="transaction_autos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bank_account;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $repeat_type;

    /**
     * @ORM\Column(type="date")
     */
    private $date_start;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $date_last;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $details;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBankAccount(): ?BankAccount
    {
        return $this->bank_account;
    }

    public function setBankAccount(?BankAccount $bank_account): self
    {
        $this->bank_account = $bank_account;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getRepeatType(): ?string
    {
        return $this->repeat_type;
    }

    public function setRepeatType(string $repeat_type): self
    {
        $this->repeat_type = $repeat_type;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->date_start;
    }

    public function setDateStart(\DateTimeInterface $date_start): self
    {
        $this->date_start = $date_start;

        return $this;
    }

    public function getDateLast(): ?\DateTimeInterface
    {
        return $this->date_last;
    }

    public function setDateLast(\DateTimeInterface $date_last): self
    {
        $this->date_last = $date_last;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }
}
