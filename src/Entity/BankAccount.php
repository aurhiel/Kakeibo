<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\BankAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(BankAccountRepository::class)]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $label;

    #[ORM\OneToMany(mappedBy: 'bank_account', targetEntity: Transaction::class, orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: true)]
    #[ORM\OrderBy(['date' => 'DESC', 'id' => 'DESC'])]
    private Collection $transactions;

    #[ORM\ManyToOne(targetEntity: BankBrand::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: true)]
    private ?BankBrand $bank_brand = null;

    #[ORM\ManyToOne(targetEntity: Currency::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $currency = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER', inversedBy: 'bankAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool$is_default = false;

    private ?float $balance = null;

    #[ORM\OneToMany(mappedBy: 'bank_account', targetEntity: TransactionAuto::class, orphanRemoval: true)]
    private $transaction_autos;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private $is_archived = false;

    public function __construct(?User $user)
    {
        $this->transactions = new ArrayCollection();

        $this->setUser($user);
        $this->transaction_autos = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setBankAccount($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getBankAccount() === $this) {
                $transaction->setBankAccount(null);
            }
        }

        return $this;
    }

    // TODO: Replace by SQL query !!
    public function getBalance(): float
    {
        if (is_null($this->balance)) {
            $now = new \DateTime();
            foreach ($this->transactions as $transaction) {
                // Add transaction only when < current date ($now)
                //  (future transactions will be displayed elsewhere)
                if ($transaction->getDate() <= $now) {
                    $this->balance += $transaction->getAmount();
                }
            }
        }

        return (float) $this->balance;
    }

    public function getBankBrand(): ?BankBrand
    {
        return $this->bank_brand;
    }

    public function setBankBrand(?BankBrand $bank_brand): self
    {
        $this->bank_brand = $bank_brand;

        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIsDefault(): bool
    {
        return $this->is_default;
    }


    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function setIsDefault(bool $is_default): self
    {
        $this->is_default = $is_default;

        return $this;
    }

    /**
     * @return Collection<int, TransactionAuto>
     */
    public function getTransactionAutos(): Collection
    {
        return $this->transaction_autos;
    }

    public function addTransactionAuto(TransactionAuto $transactionAuto): self
    {
        if (!$this->transaction_autos->contains($transactionAuto)) {
            $this->transaction_autos[] = $transactionAuto;
            $transactionAuto->setBankAccount($this);
        }

        return $this;
    }

    public function removeTransactionAuto(TransactionAuto $transactionAuto): self
    {
        // set the owning side to null (unless already changed)
        if ($this->transaction_autos->removeElement($transactionAuto) && $transactionAuto->getBankAccount() === $this) {
            $transactionAuto->setBankAccount(null);
        }

        return $this;
    }

    public function getIsArchived(): bool
    {
        return $this->is_archived;
    }

    public function isArchived(): bool
    {
        return $this->is_archived;
    }

    public function setIsArchived(bool $is_archived): self
    {
        $this->is_archived = $is_archived;

        return $this;
    }

    public function toggleIsArchived(): self
    {
        $this->is_archived = !$this->is_archived;

        return $this;
    }
}
