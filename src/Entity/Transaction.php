<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
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
     * @ORM\ManyToOne(targetEntity="App\Entity\BankAccount", inversedBy="transactions", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bank_account;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $details;

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

    public function getDate(): ?\DateTimeInterface
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
}
