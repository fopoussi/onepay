<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OnepayLoginHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnepayLoginHistoryRepository::class)]
#[ApiResource]
class OnepayLoginHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loginHistories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnepayUser $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $loginDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isSuccessful = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $additionalData = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLoginDate(): ?\DateTimeInterface
    {
        return $this->loginDate;
    }

    public function setLoginDate(\DateTimeInterface $loginDate): static
    {
        $this->loginDate = $loginDate;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getDeviceInfo(): ?string
    {
        return $this->deviceInfo;
    }

    public function setDeviceInfo(?string $deviceInfo): static
    {
        $this->deviceInfo = $deviceInfo;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function isSuccessful(): ?bool
    {
        return $this->isSuccessful;
    }

    public function setIsSuccessful(bool $isSuccessful): static
    {
        $this->isSuccessful = $isSuccessful;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(?array $additionalData): static
    {
        $this->additionalData = $additionalData;
        return $this;
    }
}
