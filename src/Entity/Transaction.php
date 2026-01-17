<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(TransactionRepository::class)]
#[ORM\Index(columns: ['bank_account_id', 'date'], name: 'idx_balance_lookup')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private $label;

    #[ORM\Column(type: Types::FLOAT)]
    private float $amount;

    #[ORM\ManyToOne(targetEntity: BankAccount::class, fetch: 'EAGER', inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private BankAccount $bank_account;

    #[ORM\ManyToOne(targetEntity: Category::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\OneToOne(targetEntity: Transaction::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Transaction $bankTransferLinkedTransaction = null;

    public function getId(): int
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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function fillWithTransAuto(TransactionAuto $trans_auto): self
    {
        $this->setBankAccount($trans_auto->getBankAccount())
          ->setLabel($trans_auto->getLabel())
          ->setDetails($trans_auto->getDetails())
          ->setCategory($trans_auto->getCategory())
          ->setAmount($trans_auto->getAmount())
        ;

        return $this;
    }

    public function getBankTransferLinkedTransaction(): ?self
    {
        return $this->bankTransferLinkedTransaction;
    }

    public function setBankTransferLinkedTransaction(?self $bankTransferLinkedTransaction): self
    {
        $this->bankTransferLinkedTransaction = $bankTransferLinkedTransaction;

        return $this;
    }
}
