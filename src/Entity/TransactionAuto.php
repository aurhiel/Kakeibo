<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\TransactionAutoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(TransactionAutoRepository::class)]
class TransactionAuto
{
    // RT = Repeat Type
    const RT_YEARLY = 'YEARLY';
    const RT_MONTHLY = 'MONTHLY';
    const RT_WEEKLY = 'WEEKLY';
    const RT_DAILY = 'DAILY';

    const RT_LIST = [
        self::RT_YEARLY,
        self::RT_MONTHLY,
        self::RT_WEEKLY,
        self::RT_DAILY,
    ];

    const ERR_UNKNOWN_RTYPE = -1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $label;

    #[ORM\Column(type: Types::FLOAT)]
    private float $amount;

    #[ORM\ManyToOne(targetEntity: BankAccount::class, inversedBy: 'transaction_autos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BankAccount $bank_account = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $repeat_type;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date_start;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_last = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $is_active;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBankAccount(): BankAccount
    {
        return $this->bank_account;
    }

    public function setBankAccount(?BankAccount $bank_account): self
    {
        $this->bank_account = $bank_account;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getRepeatType(): string
    {
        return $this->repeat_type;
    }

    public function setRepeatType(string $repeat_type): self
    {
        $this->repeat_type = $repeat_type;

        return $this;
    }

    public function getDateStart(): \DateTimeInterface
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

    public function getDateNext(): \DateTimeInterface
    {
        if (null !== $this->getDateLast()) {
            $nextDate = \DateTimeImmutable::createFromInterface($this->getDateLast());

            return match ($this->getRepeatType()) {
                self::RT_DAILY => $nextDate->modify('+1 day'),
                self::RT_WEEKLY => $nextDate->modify('+1 week'),
                self::RT_MONTHLY => $nextDate->modify('+1 month'),
                self::RT_YEARLY => $nextDate->modify('+1 year'),
            };
        }

        return $this->getDateStart();
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

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function wasExecutedDuringPeriod(): bool
    {
        if (null === $this->getDateLast()) {
            return false;
        }

        $format = match ($this->getRepeatType()) {
            self::RT_DAILY => 'Y-m-d',
            self::RT_WEEKLY => 'o-W',
            self::RT_MONTHLY => 'Y-m',
            self::RT_YEARLY => 'Y',
        };

        return date($format) === $this->getDateLast()->format($format);
    }
}
