<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MobileMoneyAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MobileMoneyAccountRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete(),
        new Post(
            uriTemplate: '/mobile-money/accounts/{id}/verify',
            name: 'verify'
        ),
        new Put(
            uriTemplate: '/mobile-money/accounts/{id}/default',
            name: 'set_default'
        ),
        new Get(
            uriTemplate: '/mobile-money/accounts/{id}/balance',
            name: 'get_balance'
        )
    ]
)]
class MobileMoneyAccount
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

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['ORANGE_MONEY', 'MTN_MOMO'])]
    private ?string $provider = null;

    #[ORM\ManyToOne(inversedBy: 'mobileAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnepayUser $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PhoneNumber $phoneNumber = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $balance = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSync = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
        // Automatically set provider based on number prefix
        $prefix = substr($number, 1, 1);
        $this->provider = match($prefix) {
            '5', '7', '8' => 'MTN_MOMO',
            '9', '6' => 'ORANGE_MONEY',
            default => null
        };
        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
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

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?PhoneNumber $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
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

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): static
    {
        $this->balance = $balance;
        return $this;
    }

    public function getLastSync(): ?\DateTimeImmutable
    {
        return $this->lastSync;
    }

    public function setLastSync(?\DateTimeImmutable $lastSync): static
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
