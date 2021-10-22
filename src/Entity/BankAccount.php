<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BankAccountRepository")
 */
class BankAccount
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $label;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="bank_account", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true)
     * @ORM\OrderBy({"date" = "DESC", "id" = "DESC"})
     */
    private $transactions;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BankBrand", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $bank_brand;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="bankAccounts", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_default;

    /**
    * According to transactions sum
    */
    private $balance = null;

    /**
     * @ORM\OneToMany(targetEntity=TransactionAuto::class, mappedBy="bank_account", orphanRemoval=true)
     */
    private $transaction_autos;

    public function __construct($user)
    {
        $this->transactions = new ArrayCollection();

        $this->setUser($user);
        $this->transactionAutos = new ArrayCollection();
        $this->transaction_autos = new ArrayCollection();
    }

    public function getId()
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
     * @return Collection|Transaction[]
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

    public function getBalance(): float
    {
        if (is_null($this->balance) === true) {
            $now = new \DateTime();
            foreach ($this->transactions as $key => $transaction) {
                // Add transaction only when < current date ($now)
                //  (future transactions will be displayed elsewhere)
                if ($transaction->getDate() <= $now)
                    $this->balance += $transaction->getAmount();
            }
        }

        return $this->balance;
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

    public function getIsDefault(): ?bool
    {
        return $this->is_default;
    }

    public function setIsDefault(bool $is_default): self
    {
        $this->is_default = $is_default;

        return $this;
    }

    /**
     * @return Collection|TransactionAuto[]
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
        if ($this->transaction_autos->removeElement($transactionAuto)) {
            // set the owning side to null (unless already changed)
            if ($transactionAuto->getBankAccount() === $this) {
                $transactionAuto->setBankAccount(null);
            }
        }

        return $this;
    }
}
