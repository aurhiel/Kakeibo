<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $label;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private string $slug;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $color;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $icon;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $import_regex = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $is_default;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    public function getIsDefault(): bool
    {
        return $this->is_default;
    }

    public function setIsDefault(bool $is_default): self
    {
        $this->is_default = $is_default;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getImportRegex(): ?string
    {
        return $this->import_regex;
    }

    public function setImportRegex(?string $import_regex): self
    {
        $this->import_regex = $import_regex;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
