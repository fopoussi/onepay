<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OnepayFailedTransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnepayFailedTransactionRepository::class)]
#[ApiResource]
class OnepayFailedTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'onepayFailedTransactions')]
    private ?OnepayTransaction $transaction_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $error_message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransactionId(): ?OnepayTransaction
    {
        return $this->transaction_id;
    }

    public function setTransactionId(?OnepayTransaction $transaction_id): static
    {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(string $error_message): static
    {
        $this->error_message = $error_message;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
