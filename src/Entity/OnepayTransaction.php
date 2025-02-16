<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\OnepayTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OnepayTransactionRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
        paginationEnabled: true,
        paginationItemsPerPage: 20, // Nombre d'éléments par page par défaut
        paginationClientItemsPerPage: true // Permet au client de spécifier le nombre d'éléments par page
        ),
        new Post(
            processor: OnepayTransactionProcessor::class // Utilisez votre processeur personnalisé
        ),
        new Put(),
        new Delete(),
    ],
    filters: ['onepay_transaction.status_filter', 'onepay_transaction.amount_filter']
)]
class OnepayTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'onepayTransactions')]
    #[Assert\NotNull(message: "L'utilisateur est obligatoire.")]
    private ?OnepayUser $user_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type de transaction est obligatoire.")]
    #[Assert\Choice(choices: ['credit', 'transfer'], message: "Le type de transaction doit être 'credit' ou 'transfer'.")]
    private ?string $transaction_type = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le montant doit être positif ou zéro.")]
    private ?float $amount = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    #[Assert\Choice(choices: ['pending', 'completed', 'failed'], message: "Le statut doit être 'pending', 'completed' ou 'failed'.")]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(length: 50)]
    private ?string $from_operator = null;

    #[ORM\Column(length: 50)]
    private ?string $to_operator = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $receiver_phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $operator = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone_number = null;

    /**
     * @var Collection<int, OnepayFailedTransaction>
     */
    #[ORM\OneToMany(targetEntity: OnepayFailedTransaction::class, mappedBy: 'transaction_id')]
    private Collection $onepayFailedTransactions;

    public function __construct()
    {
        $this->onepayFailedTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?OnepayUser
    {
        return $this->user_id;
    }

    public function setUserId(?OnepayUser $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->transaction_type;
    }

    public function setTransactionType(string $transaction_type): static
    {
        $this->transaction_type = $transaction_type;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getFromOperator(): ?string
    {
        return $this->from_operator;
    }

    public function setFromOperator(string $from_operator): static
    {
        $this->from_operator = $from_operator;

        return $this;
    }

    public function getToOperator(): ?string
    {
        return $this->to_operator;
    }

    public function setToOperator(string $to_operator): static
    {
        $this->to_operator = $to_operator;

        return $this;
    }

    public function getReceiverPhone(): ?string
    {
        return $this->receiver_phone;
    }

    public function setReceiverPhone(?string $receiver_phone): static
    {
        $this->receiver_phone = $receiver_phone;

        return $this;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(?string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * @return Collection<int, OnepayFailedTransaction>
     */
    public function getOnepayFailedTransactions(): Collection
    {
        return $this->onepayFailedTransactions;
    }

    public function addOnepayFailedTransaction(OnepayFailedTransaction $onepayFailedTransaction): static
    {
        if (!$this->onepayFailedTransactions->contains($onepayFailedTransaction)) {
            $this->onepayFailedTransactions->add($onepayFailedTransaction);
            $onepayFailedTransaction->setTransactionId($this);
        }

        return $this;
    }

    public function removeOnepayFailedTransaction(OnepayFailedTransaction $onepayFailedTransaction): static
    {
        if ($this->onepayFailedTransactions->removeElement($onepayFailedTransaction)) {
            // set the owning side to null (unless already changed)
            if ($onepayFailedTransaction->getTransactionId() === $this) {
                $onepayFailedTransaction->setTransactionId(null);
            }
        }

        return $this;
    }
}