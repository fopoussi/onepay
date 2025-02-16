<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\OnepayAuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnepayAuditLogRepository::class)]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['message_class'])]
#[ORM\Index(columns: ['user_id'])]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    security: "is_granted('ROLE_ADMIN')"
)]
class OnepayAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $messageClass = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $messageData = null;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column]
    private bool $success = false;

    #[ORM\Column(type: Types::FLOAT)]
    private float $duration = 0.0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageClass(): ?string
    {
        return $this->messageClass;
    }

    public function setMessageClass(string $messageClass): static
    {
        $this->messageClass = $messageClass;
        return $this;
    }

    public function getMessageData(): ?string
    {
        return $this->messageData;
    }

    public function setMessageData(string $messageData): static
    {
        $this->messageData = $messageData;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;
        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): static
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }
}
