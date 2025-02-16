<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PhoneNumberRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete()
    ]
)]
class PhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^6[2,5-9][0-9]{7}$/',
        message: 'Le numéro doit commencer par 6 et avoir 9 chiffres au total'
    )]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['MTN', 'ORANGE', 'CAMTEL'])]
    private ?string $operator = null;

    #[ORM\ManyToOne(inversedBy: 'phoneNumbers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnepayUser $user = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;
        // Automatically set operator based on number prefix
        $prefix = substr($number, 1, 1);
        $this->operator = match($prefix) {
            '5', '7', '8' => 'MTN',
            '9', '6' => 'ORANGE',
            '2' => 'CAMTEL',
            default => null
        };
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;
        return $this;
    }

    public function getUser(): ?OnepayUser
    {
        return $this->user;
    }

    public function setUser(?OnepayUser $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
